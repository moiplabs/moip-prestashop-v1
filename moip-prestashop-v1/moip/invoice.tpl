<p>
<br>
{if $orderPaymentMoip}
<fieldset style="width: 400px;">

	<legend>
        <img src="../modules/moip/logo.gif" />
     {l s='Moip Pagamentos' mod='Moip Pagamentos'}
	</legend>

	<div id="info" border: solid red 1px;">
	<table>
	<tr><td colspan="2">Pagamento realizado por meio do <a href="http://www.moip.com.br"  target="_blank"><u>Moip</u></a></td></tr>
	<tr><td>Forma de pagamento:</td> <td><b>{$paymentFormText}</b> ({$paymentFormInstitutionText})</td></tr>

    	<tr><td>Status Moip:</td> <td><b>{$paymentStatusText}</b></td></tr>

      {if $paymentCode}
	<tr><td>Código Moip:</td> <td><b>{$paymentCode}</b></td></tr>
        {/if}
      {if $paymentValue}
	<tr><td>Toral pago:</td> <td><b>{$paymentValue}</b></td></tr>
      {/if}
      {if !$paymentStatusFinal}
	<tr><td colspan="2"><br><b>Aguardando confirmação de pagamento.</b></td></tr>
      {/if}
	</table>
	</div>
</fieldset>
{/if}
</p>