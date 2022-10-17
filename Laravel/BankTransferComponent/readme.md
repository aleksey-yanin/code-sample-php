# Laravel компонент для банковских переводов

Точка входа для инициации исходящего банковского перевода.
Помогает корректно создать запись с параметрами перевода, валидирует их и ставит перевод в очередь.
Удобно использовать с помощью подсказок в IDE, нет необходимости открывать исходник.

## usage

```
$transferId = BankTransfer::create(
                  $bankId,
                  $branchId,
                  $accountType,
                  $accountNumber,
                  $recipientsName,
                  $amount,
              )->forGoods($goodsId, $goodsPrice, $deliveryCost)
                  ->setUserName(Auth::user()->login)
                  ->commit();

```