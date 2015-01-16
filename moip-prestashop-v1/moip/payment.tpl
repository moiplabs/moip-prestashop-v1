<p class="payment_module" id="payment_module_moip">
     <link rel="stylesheet" type="text/css" href="{$modules_dir}/moip/css/default.css" />
    <script type="text/javascript" src="https://www.moip.com.br/transparente/MoipWidget-v2.js"></script>
     <script type="text/javascript" src="{$modules_dir}/moip/script/jquery.validate.js"></script> 
     <script type="text/javascript" src="{$modules_dir}/moip/script/jquery.maskedinput.js"></script> 
     <script type="text/javascript" src="{$modules_dir}/moip/script/moip.js"></script>


    {literal}
        <script type="text/javascript">
            $(document).ready(function(){
                MoipPagamentos();
            });    
        </script>             
    {/literal}     


<div id="MoipWidget" callback-method-success="callbackSuccess" callback-method-error="callbackError" />

<div id="prin">
    <p id="p_warning"></p>
    <div class="alert"></div>

    <form class="formulario" id="formulario" method="post" action="">

        <input type="hidden" id="tokenToMoip" name="tokenToMoip" value="{$moip_token}" size="85" />

        <input type="hidden" name="paymentForm" value="" />
        <input type="hidden" name="idCart" value="{$idCart}" />
        <input type="hidden" name="valorAVista" value="{$orderValue}" />
        <input type="hidden" name="uniqueIdForMoip" value="{$uniqueIdForMoip}" />
        <input type="hidden" name="moipRequest" value='{$moipRequest}' />

        {if $payment_error == 'Falha'}
            <p class="warning">
                Não foi possível gerar seu pagamento pelo Moip. <br>
                Erro: {$payment_error_message}
            </p>
        {else}
            {if $credito}
                <div class="moipay">

                    <legend>Pagar com Cartão de Crédito em até 12 vezes.</legend>
                    
                    <label class="pchoice" title="Pagar com VISA">
                        <input type="radio" name="payment" value="Credito" id="Visa" />
                        <img src="{$modules_dir}/moip/images/visa.png" alt="Visa">
                    </label>
                    <label class="pchoice" title="Pagar com MasterCard">
                        <input type="radio" name="payment" value="Credito" id="Mastercard" />
                        <img src="{$modules_dir}/moip/images/master.png" alt="Mastercard">
                    </label>
                    <label class="pchoice" title="Pagar com AmericanExpress">
                        <input type="radio" name="payment" value="Credito" id="AmericanExpress" />
                        <img src="{$modules_dir}/moip/images/american.png" alt="AmericanExpress">
                    </label>
                    <label class="pchoice" title="Pagar com Dinners">
                        <input type="radio" name="payment" value="Credito" id="Dinners" />
                        <img src="{$modules_dir}/moip/images/dinners.png" alt="Dinners">
                    </label>
                    <label class="pchoice" title="Pagar com Hipercard">
                        <input type="radio" name="payment" value="Credito" id="Hipercard"/>
                        <img src="{$modules_dir}/moip/images/hiper.png" alt="Hipercard">
                    </label>

                    <div class="escolha payform" id="Credito">
                        <ul id="alert-area">
                    
                        </ul>
                            <legend>Dados do cartão</legend>
                            <ul>
                                <li>
                                    <label>Número de parcelas</label>
                                    <select name="parcelamentoCartao" id="parcelamentoCartao">
                                        <option value="1" label="Pagamento à vista" title="Parcela única de R$ {$orderValueBr}">Pagamento à vista</option>
                                    </select>
                                </li>
                                <li class="parcelamentoCartao">Parcela única de R$ {$orderValueBr}</li>
                                <br class="clear">
                                <li>
                                    <label>Número do cartão</label>
                                    <input type="text" name="cartaoNumero" id="cartaoNumero" required class="required input" />
                                </li>
                                <li>
                                    <label>Validade</label>
                                     <select name="cartaoMes" id="cartaoMes" class="required input">
                                        <option value="" selected="">Mês</option>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                    </select>
                                    /
                                    <select name="cartaoAno" id="cartaoAno" class="required input">
                                        <option value="" selected="">Ano</option>
                                        <option value="12">2012</option>
                                        <option value="13">2013</option>
                                        <option value="14">2014</option>
                                        <option value="15">2015</option>
                                        <option value="16">2016</option>
                                        <option value="17">2017</option>
                                        <option value="18">2018</option>
                                        <option value="19">2019</option>
                                        <option value="20">2020</option>
                                        <option value="21">2021</option>
                                        <option value="22">2022</option>
                                        <option value="23">2023</option>
                                        <option value="24">2024</option>
                                        <option value="25">2025</option>
                                    </select>
                                </li>
                                <li>
                                    <label>Código de segurança (CVV)</label>
                                    <input type="text" name="segurancaNumero" id="segurancaNumero" size="4" class="required input" />
                                </li>
                            </ul>
                            <br><br>
                            <legend>Dados do titular</legend>
                            <ul>
                                <li>
                                    <label>Nome do titular</label>
                                    <input type="text" name="nomePortador" id="nomePortador" required class="required input" />
                                </li>
                                <li>
                                    <label>Data de nascimento <span>(DD/MM/AAAA)</span></label>
                                    <input type="text" name="dataPortador" id="dataPortador" required class="required input" />
                                </li>
                                
                                <li>
                                    <label>CPF</label>
                                    <input type="text" name="cpfPortador" id="cpfPortador" required class="required input" />
                                </li>
                                <li>
                                    <label>Telefone de contato</label>
                                    <input type="text" name="telefonePortador" id="telefonePortador" required class="required input" />
                                </li>
                                <li>
                                    <button class="exclusive" id="CartaoCredito" name="submit" type="submit">Efetuar pagamento</button> <img src="modules/moip/icons/spinner.gif" id="spinnerCartaoCredito" alt="Aguarde..." style="display: none;" />
                                </li>
                            </ul>
                        <br class="clear">
                    </div>
                    
                </div>
            {/if}
            {if $debito}
                <div class="moipay">

                    <legend>Pagar utilizando Débito em conta</legend>

                    <label class="pchoice" title="Pagar com Banrisul">
                        <input type="radio" name="payment" value="Debito" id="Banrisul" />
                        <img src="{$modules_dir}/moip/images/banrisul.png" alt="Banrisul">
                    </label>
                   <label class="pchoice" title="Pagar com Itau">
                        <input type="radio" name="payment" value="Debito" id="Itau" />
                        <img src="{$modules_dir}/moip/images/itau.jpg" alt="Itau">
                    </label>
                    <label class="pchoice" title="Pagar com Bradesco">
                        <input type="radio" name="payment" value="Debito" id="Bradesco" />
                        <img src="{$modules_dir}/moip/images/bradesco.jpg" alt="Bradesco">
                    </label>
                    <label class="pchoice" title="Pagar com Banco do Brasil">
                        <input type="radio" name="payment" value="Debito" id="BancoDoBrasil" />
                        <img src="{$modules_dir}/moip/images/bb.jpg" alt="Banco do Brasil">
                    </label>

                    <div class="escolha payform" id="Debito">

                        <div id="div-debito" class="escolha-side-full">

                            <legend>Parcela única de R$ {$orderValueBr}  </legend>
                            <p>Você será redirecionado ao site de seu banco para concluir o pagamento.</p>
                            <button class="exclusive" id="DebitoBancario" name="submit" type="submit">Efetuar pagamento</button>
                            <img src="{$modules_dir}/moip/icons/spinner.gif" id="spinnerDebitoBancario" alt="Aguarde..." style="display: none;" />
                        </div>
                        
                    </div>
                    <br class="clear">
                </div>
            {/if}
            {if $boleto}
                <div class="moipay">

                    <legend>Pagar utilizando Boleto Bancário</legend>

                    <label class="pchoice" title="Pagar com Boleto Bradesco">
                        <input type="radio" name="payment" value="Boleto" id="BoletoMoip" />
                        <img src="{$modules_dir}/moip/images/boleto.jpg" alt="Boleto">
                    </label>

                    <div class="escolha payform" id="Boleto">
                        <div id="div-boleto" class="escolha-side-full">
                            <legend>Parcela única de R$ {$orderValueBr}  </legend>
                            <p>Você deverá efetuar o pagamento do boleto em até três (3) dias após sua impressão.</p>
                            
                            <button class="exclusive" id="BoletoBancario" name="submit" type="submit">Efetuar pagamento</button>
                            <img src="{$modules_dir}/moip/icons/spinner.gif" id="spinnerBoletoBancario" alt="Aguarde..." style="display: none;" />
                                 
                        </div>
                        
                    </div>
                    <br class="clear">
                </div>
            {/if}
        {/if}
    </form>
</div>
</div>
</p>
