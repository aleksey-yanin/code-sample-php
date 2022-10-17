<?php


namespace App\Business\Finance;


use App\Business\Accounting\TransactionCreator;
use App\Jobs as Jobs;
use App\Models as Models;
use App\Services\LegacyApiClient;
use Carbon\Carbon;

/**
 * Class BankTransfer
 *
 * @package App\Business\Finance
 */
class BankTransfer
{
    
    // handlers
    const HANDLER_DEALING = 'dealing';
    
    const HANDLER_GOODS = 'goods';
    
    const HANDLER_CONTRACTOR = 'contractor';
    
    const HANDLER_EXTERNAL = 'external';
    
    const HANDLER_TEST = 'test';
    
    const ERROR_CODE_OK = 0;
    const ERROR_CODE_INVALID_PARAMETER = 10;
    const ERROR_CODE_INTERNAL_ERROR = 20;
    const ERROR_CODE_DUPLICATE = 30;
    const ERROR_CODE_SCENARIO_ERROR = 40;
    const ERROR_CODE_SECURITY = 50;
    
    
    protected static $jibunTargetAccountTypes = [
        '普通',
        '当座',
        '貯蓄',
        '普通預金',
        '当座預金',
        '貯蓄預金',
    ];
    
    /** @var \App\Models\Finance\BankTransfer */
    protected $model;
    
    /**
     * @var int
     */
    protected $delayUntil;
    
    /**
     * this just creates a new model, and does not save it
     *
     * @param string      $bankIdOrName
     * @param string      $branchIdOrName
     * @param string      $accountType
     * @param int         $accountNumber
     * @param string      $accountHolder
     * @param int         $amount
     * @param string|null $sourceAccountName
     * @param string|null $transferMemo
     * @param string|null $senderName
     *
     * @return static
     */
    public static function create(
        string $bankIdOrName,
        string $branchIdOrName,
        string $accountType,
        int $accountNumber,
        string $accountHolder,
        int $amount,
        string $sourceAccountName = null, // null == default
        string $transferMemo = null, // optional
        string $senderName = null // optional
    ): self
    {
        $instance = new static();
        $bank     = $instance->getBank($bankIdOrName);
        $branch   = $instance->getBranch($bank, $branchIdOrName);
        
        $model = new Models\Finance\BankTransfer();
        $model->setBank($bank)
            ->setBranch($branch)
            ->setAccountType($accountType)
            ->setAccountNumber($accountNumber)
            ->setAccountHolder($accountHolder)
            ->setAmount($amount);
        
        if (is_null($sourceAccountName)) {
            $sourceAccountName = Models\Accounting\CompanyAccount::ACCOUNT_JIBUN1;
        }
        $model->setSourceAccountName($sourceAccountName);
        
        if ($senderName) {
            $model->setSenderName($senderName);
        }
        
        if ($transferMemo) {
            $model->setTransferMemo($transferMemo);
        }
    
        $instance->model = $model;
        
        return $instance;
    }
    
    /**
     * @param string $bankIdOrName
     *
     * @return \App\Models\Finance\Bank
     */
    public static function getBank(string $bankIdOrName): Models\Finance\Bank
    {
        try {
            $bank = Models\Finance\Bank::find($bankIdOrName);
            if (!$bank instanceof Models\Finance\Bank) {
                $bank = Models\Finance\Bank::where(['name' => $bankIdOrName])->firstOrFail();
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            throw new \InvalidArgumentException("the bank '$bankIdOrName' not found");
        }
        
        return $bank;
    }
    
    /**
     * @param \App\Models\Finance\Bank $bank
     * @param                          $branchIdOrName
     *
     * @return \App\Models\Finance\BankBranch
     */
    public static function getBranch(Models\Finance\Bank $bank, $branchIdOrName): Models\Finance\BankBranch
    {
        try {
            /** @var Models\Finance\BankBranch $branch */
            $branch = $bank->branches()->where('id', '=', $branchIdOrName)->first();
            if (!$branch instanceof Models\Finance\BankBranch) {
                $branch = $bank->branches()->where('name', '=', $branchIdOrName)->firstOrFail();
            }
            
            return $branch;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            throw new \InvalidArgumentException("the bank branch '$branchIdOrName' not found");
        }
    }
    
    /**
     * @param string $auctionId
     *
     * @return $this
     */
    public function forDealing(string $auctionId) : self
    {
        $this->model
            ->setTransferMemo($auctionId)
            ->setHandler(static::HANDLER_DEALING)
            ->setPayload([
                'auctionId' => $auctionId
                         ])
        ;
        
        return $this;
    }
    
    /**
     * @param int $goodsId
     * @param int $goodsPrice
     * @param int $deliveryCost
     *
     * @return $this
     */
    public function forGoods(int $goodsId, int $goodsPrice, int $deliveryCost) : self
    {
        $this->model
            ->setHandler(static::HANDLER_GOODS)
            ->setPayload([
                'goodsId' => $goodsId,
                'goodsPrice' => $goodsPrice,
                'deliveryCost' => $deliveryCost,
                         ])
        ;
        
        return $this;
    }
    
    /**
     * @return $this
     */
    public function forExternalTransfer() : self
    {
        $this->model->setHandler(static::HANDLER_EXTERNAL);
        
        return $this;
    }
    
    /**
     * @return $this
     */
    public function forTesting() : self
    {
        $this->model
            ->setHandler(static::HANDLER_TEST)
            ->setTransferMemo('testing')
        ;
        
        return $this;
    }
    
    /**
     * @param $userName
     *
     * @return $this
     */
    public function setUserName($userName) : self
    {
        $this->model->setUserName($userName);
        
        return $this;
    }
    
    /**
     * @param $datetime
     *
     * @return $this
     */
    public function delayUntil($datetime): self
    {
        if ($datetime instanceof Carbon) {
            $this->delayUntil = $datetime;
        }
        
        return $this;
    }
    
    /**
     * confirm payment
    /*
     * @return int
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function commit()
    {
        // 1. validate
        $this->validate();
        
        // 2. save
        $this->model->save();
        
        // 3. enqueue
        Jobs\BankPayment::dispatch($this->model->id)->onQueue('bank_transfers')->delay($this->delayUntil);
        
        return $this->model->id;
    }
    
    /**
     * @param \App\Models\Finance\BankTransfer $paymentRecord
     *
     * @throws \Exception
     */
    public static function handleSuccess(Models\Finance\BankTransfer $paymentRecord)
    {
        switch ($paymentRecord->handler) {
            case static::HANDLER_DEALING:
                TransactionCreator::forDealing(
                    $paymentRecord->payload['auctionId'],
                    $paymentRecord->source_account_name,
                    $paymentRecord->amount,
                    $paymentRecord->fee_amount,
                    $paymentRecord->payment_date
                );
                
                break;
            case static::HANDLER_GOODS:
                $goodsId = $paymentRecord->payload['goodsId'];
                TransactionCreator::forGoods(
                    $goodsId,
                    $paymentRecord->source_account_name,
                    $paymentRecord->amount,
                    $paymentRecord->fee_amount,
                    $paymentRecord->payment_date
                );
                
                // update the goods
                /** @var LegacyApiClient $legacyApiClient */
                $legacyApiClient = resolve(LegacyApiClient::class);
                $completeGoodsPayment = $legacyApiClient->completeGoodsPayment(
                    $goodsId,
                    $paymentRecord->payload['goodsPrice'],
                    $paymentRecord->payload['deliveryCost']
                );
    
                if (!isset($completeGoodsPayment['success']) || $completeGoodsPayment['success'] == false) {
                    $errorMessage = $completeGoodsPayment['error'] ?? 'unexpected API response';
                    throw new \Exception("Failed to complete goods payment: $errorMessage");
                }
                
                break;
            
            case static::HANDLER_CONTRACTOR:
                // contractor bank transfers not implemented yet
                break;
            
            case static::HANDLER_EXTERNAL:
                // nothing to do here
                break;
            
            case static::HANDLER_TEST:
                // test bank transfer is not implemented yet
                break;
            
            default:
                throw new \Exception("Handler '$paymentRecord->handler' is not defined");
        }
    }
    
    /**
     * validate the transfer params in the model
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function validate()
    {
        $this->model->validate();
        
        // account-specific validation
        switch ($this->model->source_account_name) {
            case Models\Accounting\CompanyAccount::ACCOUNT_JIBUN1:
            case Models\Accounting\CompanyAccount::ACCOUNT_JIBUN2:
                $this->validateJibun();
                break;
        }
    }
    
    /**
     * Jibun-specific validation rules
     */
    protected function validateJibun()
    {
        if (in_array($this->model->source_account_name, Models\Accounting\CompanyAccount::$jibunAccounts)) {
            if (!in_array($this->model->account_type, static::$jibunTargetAccountTypes)) {
                throw new \InvalidArgumentException("account type '{$this->model->account_type}' is not valid");
            }
            
            if (!empty($this->model->senderName) && strlen($this->model->senderName) > 20) {
                throw new \InvalidArgumentException('sender name is too long');
            }
            
            if (!empty($this->model->transferMemo) && strlen($this->model->transferMemo) > 20) {
                throw new \InvalidArgumentException('transfer memo is too long');
            }
        }
    }
}