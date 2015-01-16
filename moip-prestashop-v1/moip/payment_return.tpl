{capture name=path}{l s='Confirmação de pagamento'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}


<h2>{l s='Moip Pagamentos' mod='moip'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $payment_form == "CartaoCredito" || $payment_form == "CartaoDeCredito"}
    {if $payment_status == "1"}

        <p class="warning">
                {l s='Seu pagamento foi autorizado.' mod='Moip'}
        </p>
        <br>
        <span>O pagamento da sua compra será processado pelo <a href="http://moip.com.br" target="_blank">Moip</a>.<br>
        Em breve, você receberá um e-mail de confirmação do pagamento junto com o código da transação.<span>
        <br><br>
        <span>Forma de pagamento utilizado: <strong>{$payment_form_institution}</strong></span><br>
        <span>Identificador da transação no Moip: <strong>{$payment_code}</strong></span>
        <br>
        <br>
        <span><strong>Atenção:</strong> Este pagamento será registrado como "<strong>moip.com.br</strong>" em sua fatura do cartão de crédito, com o valor declarado de R$ {$payment_value}.</span>
        <br>
        <br>

    {elseif $payment_status == "5"}

        <p class="warning">
                {l s='Seu pagamento foi cancelado.' mod='Moip'}
        </p>
        <br>
        <span>Seu pagamento foi cancelado, possível razão "{$payment_classification}".<br>
        Você poderá voltar ao carrinhos de compra e escolher outro cartão para pagamento.
        </span>
        <br><br>
        <span>Forma de pagamento utilizado: <strong>{$payment_form_institution}</strong></span><br>
        <span>Identificador da transação no Moip: <strong>{$payment_code}</strong></span>
        <br>
        <br>
        <span><strong>Atenção:</strong> Não será lançado nenhuma cobrança referente a esse pagamento em seu cartão de credito.</span>
        <br>
        <br>

    {elseif $payment_status == "6" || $payment_status == "2"}

        <p class="warning">
                {l s='Seu pagamento está sendo analisado.' mod='Moip'}
        </p>
        <br>
        <span>Sua compra será analisada em um prazo de até 48 horas.<br>
         Você será notificado via e-mail sobre a confirmação / cancelamento de seu pedido.</span>
        <br><br>
        <span>Forma de pagamento utilizado: <strong>{$payment_form_institution}</strong></span><br>
        <span>Identificador da transação no Moip: <strong>{$payment_code}</strong></span>
        <br>
        <br>
        <span><strong>Atenção:</strong> Após o pagamento será registrado o valor de "<strong>R$ {$payment_value}</strong>" como "<strong>moip.com.br</strong>" em sua fatura do cartão de crédito.</span>
        <br>
        <br>

    {else}

        <p class="warning">
          {l s='Status de pagamento não identificado.' mod='Moip'}
        </p>
        <br>
        <span>Não identificaçõs nenhuma mudaça de status em seu pagamento.<br>
         <a href="{$this_path_ssl}/../../../history.php">Voltar aos meus pedidos.</a></span>
        <br>
        <br>

    {/if}

{elseif $payment_form == "DebitoBancario"}
    <p class="warning">
            {l s='Faça o pagamento pela janela do seu banco.' mod='Moip'}
    </p>
    <br>
    <meta http-equiv='Refresh' content='5;URL={$payment_url}'>
    <span>Caso a página do seu banco({$payment_form_institution}) não abra após 5 segundos <strong><a href="{$payment_url}" target="_blank" class="azul">clique aqui</a></strong>.</span><br>
    <span>O pagamento da sua compra será processado pelo <a href="http://moip.com.br" target="_blank" class="azul">Moip</a>.<br><br>

    Assim que o pagamento for compensado você receberá um email de confirmação com o código da transação.</span><br><br>

{elseif $payment_form == "BoletoBancario"}


    <p class="warning">
       <span>Caso a página com o boleto não abra, <strong><a href="{$payment_url}" target="_blank">clique aqui</a></strong>.</span>
    </p>
    <br>
    <span>O pagamento da sua compra será processado pelo <a href="http://moip.com.br" target="_blank" class="azul">Moip</a>.<br><br>

    Assim que o pagamento do boleto for compensado você receberá um email de confirmação com o código da transação.</span><br><br>
    <hr>
    <div style="margin:20px auto; padding:20px; width:650px; border:1px solid #CCC;">
     <iframe hspace="0" vspace="0" width="650" height="900"  scrolling="no" frameBorder="0" allowtransparency="true" src="{$payment_url}"> </iframe>
    </div>
{/if}

<div align="right">
<table>
<tr>
<td>
<a href="http://www.moip.com.br" target="_blank">Pagamento online</a>&nbsp;&nbsp;
</td>
<td>
<a href="http://www.moip.com.br" target="_blank">
<img src="{$this_path_ssl}script/img/moip.png"  alt="Moip " style="vertical-align:text-bottom;" />
</a>
</td>
</tr>

</table>
</div>