    var paymentMethod;
    
    $(window).load(function(){

        if( $('#Credito').length){
            this.calcularParcelamento();
        }

        $('input[name="payment"]').click(function(){
            var form_id = this.value;
            $('.escolha:visible').fadeOut();
            $('#' + form_id).fadeIn();

            $('input:radio[name=payment]').each(function() {
                if ($(this).is(':checked'))
                    paymentForm = $(this).attr('id');
            });

            $("input:hidden[name=paymentForm]").val(paymentForm);

            /*------- MASK --------**/

            $("#telefonePortador").mask("(99)9999-9999");
            $("#cpfPortador").mask("999.999.999-99");
            $("#dataPortador").mask("99/99/9999");

            if(paymentForm == "AmericanExpress"){
                $("#cartaoNumero").mask("9999 999999 99999?9");
                $("#segurancaNumero").mask("9999");
            }else if(paymentForm == "Diners"){
                $("#cartaoNumero").mask("9999 999999 9999");
                $("#segurancaNumero").mask("999");
            }else if (paymentForm == "Hipercard"){
                $("#cartaoNumero").mask("9999 9999 9999 9999?999");
                $("#segurancaNumero").mask("999");
            }else if (paymentForm == "Mastercard"){
                $("#cartaoNumero").mask("9999 9999 9999 9999");
                $("#segurancaNumero").mask("999");
            }else if (paymentForm == "Visa"){
                $("#cartaoNumero").mask("9999 9999 9999 9999");
                $("#segurancaNumero").mask("999");
            }else{
                $("#cartaoNumero").mask("9999 9999 99999 999?9");
                $("#segurancaNumero").mask("999?9");
            }
        /*------- MASK --------**/

        });

        $('select[name=parcelamentoCartao]').click(function(){
            parcelamentoCartao = $("select[name=parcelamentoCartao]").find('option').filter(':selected').attr('title');
            $(".parcelamentoCartao").text(parcelamentoCartao);
        });

    });

    $(document).ready(function() {
        $('.calcular').click(function() {
            this.calcularParcelamento();
        });


        $('.exclusive').click(function() {
            paymentMethod = $(this).attr('id');

            if(paymentMethod == 'CartaoCredito'){

                $(".formulario").validate({
                    rules : {
                        telefonePortador : {
                            required : true
                        },
                        nomePortador: {
                            required : true
                        },
                        dataPortador: {
                            required : true
                        },
                        cpfPortador: {
                            required : true
                        }
                    },
                    messages : {
                        cartaoNumero: "Informe o número do cartão de crédito corretamente",
                        segurancaNumero: "Preencha o código de segurança",
                        cartaoMes: "Preencha o mês de vencimento do cartão",
                        cartaoAno: "Preencha o ano de vencimento do cartão",
                        telefonePortador : "Preencha o telefone do titular do cartão (<i>Ex. (11)1111-1111</i>)",
                        nomePortador : "Preencha o nome do titular do cartão",
                        dataPortador : "Preencha a data de nascimento do titular do cartão (<i>Ex. 30/11/1980</i>)",
                        cpfPortador : "Preencha o CPF do titular do cartão (<i>Ex. 111.111.111-11</i>)"
                    },
                    errorClass: "validate_erro",
                    errorElement: "li",
                    ignore: ".ignore",
                    errorLabelContainer: "#alert-area",
                    submitHandler: function() {
                        sendToCreditCard();
                    }
                });

            }else if(paymentMethod == 'DebitoBancario'){

                $.removeData($('.formulario').get(0));
                $(".formulario").validate({
                    ignore: ".required",
                    submitHandler: function() {
                        sendToDebito();
                    }
                });

            }else if(paymentMethod == 'BoletoBancario'){

                $.removeData($('.formulario').get(0));
                $(".formulario").validate({
                    ignore: ".required",
                    submitHandler: function() {                 
                        sendToBoleto();
                    }
                })

            }
        });

        /**-------------- FUNCTIONS --------------**/
        calcularParcelamento = function() {
            if($("input[name=tokenToMoip]").val()){

                $("#MoipWidget").attr("data-token", $("input[name=tokenToMoip]").val());

                var settings = {
                    cofre: '',
                    instituicao: "Visa",
                    callback: "retornoCalculoParcelamento"
                };

                $("#calcular").attr("disabled", "disabled");
                MoipUtil.calcularParcela(settings);
            
                $('.pagamentoParcelado').remove();

            }
        };

        removePercel = function(){
            $('.pagamentoParcelado').remove();
            $("#calcular").removeAttr("disabled");

        };

        retornoCalculoParcelamento = function(data) {
 
            $.each(data.parcelas, function(i, l){
                if(l.quantidade != 1){
                    l.valor = l.valor.split('.').join(',');
                    l.valor_total = l.valor_total.split('.').join(',');
                    $('#parcelamentoCartao').append('<option value="' + l.quantidade + '" label="' + l.quantidade + ' x R$ ' + l.valor + '" title="Total de R$ ' + l.valor_total + '" class="pagamentoParcelado">' + l.quantidade + ' x R$ ' + l .valor + '</option>');

                }
            });

        };

        sendToCreditCard = function() {

            $("#MoipWidget").attr("data-token", tokenIsEmpty());

            cartaoValidade = $("select[name=cartaoMes]").val() + "/" + $("select[name=cartaoAno]").val();
            cartaoFormatado = $("input[name=cartaoNumero]").val();
            cartaoNumero = cartaoFormatado.split(' ').join('')

            var settings = {
                "Forma": "CartaoCredito",
                "Instituicao": $("input[name=paymentForm]").val(),
                "Parcelas": $("select[name=parcelamentoCartao]").val(),
                "Recebimento": "AVista",
                "CartaoCredito": {
                    "Numero": cartaoNumero,
                    "Expiracao": cartaoValidade,
                    "CodigoSeguranca": $("input[name=segurancaNumero]").val(),
                    "Portador": {
                        "Nome": $("input[name=nomePortador]").val(),
                        "DataNascimento": $("input[name=dataPortador]").val(),
                        "Telefone": $("input[name=telefonePortador]").val(),
                        "Identidade": $("input[name=cpfPortador]").val()
                    }
                }
            }

            disableButton('CartaoCredito');

            MoipWidget(settings);
        }


        sendToBoleto = function() {

            $("#MoipWidget").attr("data-token", tokenIsEmpty());

            var settings = {
                "Forma": "BoletoBancario"
            }

            disableButton('BoletoBancario');

            MoipWidget(settings);

        }

        sendToDebito = function() {

            $("#MoipWidget").attr("data-token", tokenIsEmpty());

            var settings = {
                "Forma": "DebitoBancario",
                "Instituicao": paymentForm
            }

            disableButton('DebitoBancario');

            MoipWidget(settings);
        }


        callbackSuccess = function(data){
            //alert(JSON.stringify(data));
            retSuccess(data);
        //enableButton(paymentMethod);

        };


        callbackError = function(data) {
            //alert(JSON.stringify(data));
            if(data.StatusPagamento == 'Falha'){
                retErrorServer(data);
            }else{
                retError(data);
            }
            enableButton(paymentMethod);

        };

        retSuccess = function(data){

            if(data.CodigoMoIP != undefined){
                if(data.Status == 'Cancelado'){
                    if(data.Classificacao){
                        moipClassification = data.Classificacao.Descricao;
                    }else{
                        moipClassification = "Erro de processamento";
                    }


                    $.ajax({
                        type: "POST",
                        url: "modules/moip/validation.php?type=redirect",
                        dataType: 'json',
                        data: {
                            paymentForm: 'CartaoCredito',
                            paymentFormInstitution : paymentForm,
                            paymentMessage: data.Mensagem,
                            paymentCode: data.CodigoMoIP,
                            paymentStatus: data.Status,
                            paymentValue: data.TotalPago,
                            idTransaction: $("input[name=uniqueIdForMoip]").val(),
                            tokenTransaction: $("input[name=tokenToMoip]").val(),
                            id_cart: $("input[name=idCart]").val(),
                            paymentClassification: moipClassification

                        },
                        success: function(data) {
                            if (data.redirect) {

                                $(".warning_line").remove();
                                $("#p_warning").addClass("warning");
                                $("#p_warning").append('<span class="warning_line">Seu pagamento não pode ser concluído.</span>');
                                $("#p_warning").append('<span class="warning_line"><br>Tente usar outro cartão ou forma de pagamento.</span>');

                                if(data.classification){
                                //$("#p_warning").append('<span class="warning_line"><br>Motivo: '+ data.classification +'</span>');
                                }

                                enableButton(paymentMethod);

                            } else {
                                retErrorServer(data);
                            }
                        }
                    });

                }else{
                    $.ajax({
                        type: "POST",
                        url: "modules/moip/validation.php?type=redirect",
                        dataType: 'json',
                        data: {
                            paymentForm: 'CartaoCredito',
                            paymentFormInstitution : paymentForm,
                            paymentMessage: data.Mensagem,
                            paymentCode: data.CodigoMoIP,
                            paymentStatus: data.Status,
                            paymentValue: data.TotalPago,
                            idTransaction: $("input[name=uniqueIdForMoip]").val(),
                            tokenTransaction: $("input[name=tokenToMoip]").val(),
                            id_cart: $("input[name=idCart]").val()

                        },
                        success: function(data) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                retErrorServer(data);
                            }
                        }
                    });
                }

            }else if(paymentForm == "BoletoMoip"){

                window.open(data.url,'_blank');

                $.ajax({
                    type: "POST",
                    url: "modules/moip/validation.php?type=redirect",
                    dataType: 'json',
                    data: {
                        paymentForm: 'BoletoBancario',
                        paymentFormInstitution : paymentForm,
                        paymentURL: data.url,
                        paymentValue:  $("input[name=valorAVista]").val(),
                        idTransaction: $("input[name=uniqueIdForMoip]").val(),
                        tokenTransaction: $("input[name=tokenToMoip]").val(),
                        id_cart: $("input[name=idCart]").val()
                    },
                    success: function(data) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            retErrorServer(data);
                        }
                    }
                });

            }else{

                $.ajax({
                    type: "POST",
                    url: "modules/moip/validation.php?type=redirect",
                    dataType: 'json',
                    data: {
                        paymentForm: 'DebitoBancario',
                        paymentFormInstitution : paymentForm,
                        paymentURL: data.url,
                        paymentValue:  $("input[name=valorAVista]").val(),
                        idTransaction: $("input[name=uniqueIdForMoip]").val(),
                        tokenTransaction: $("input[name=tokenToMoip]").val(),
                        id_cart: $("input[name=idCart]").val()
                    },
                    success: function(data) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            retErrorServer(data);
                        }
                    }
                });
            
                window.open(data.url,'_blank');

            }

        }

        retError = function(data){

            if(!data.StatusPagamento){
                $("#alert-area").show();
                $.each(data, function(i, l){
                    if(l.Codigo == 905){
                        $("#cartaoNumero").addClass("validate_erro");
                        $("#alert-area").append('<li for="cartaoNumero" generated="true" class="validate_erro">Informe o número do cartão de crédito corretamente</li>');
                    }else if(l.Codigo == 906){
                        $("#cartaoMes").addClass("validate_erro");
                        $("#alert-area").append('<li for="cartaoMes" generated="true" class="validate_erro">Preencha o mês de vencimento do cartão.</li>');
                        $("#cartaoAno").addClass("validate_erro");
                        $("#alert-area").append('<li for="cartaoAno" generated="true" class="validate_erro">Preencha o ano de vencimento do cartão.</li>');
                    }else if(l.Codigo == 907){
                        $("#segurancaNumero").addClass("validate_erro");
                        $("#alert-area").append('<li for="segurancaNumero" generated="true" class="validate_erro">Preencha o código de segurança</li>');
                    }else if(l.Codigo == 909){
                        $("#nomePortador").addClass("validate_erro");
                        $("#alert-area").append('<li for="nomePortador" generated="true" class="validate_erro">Preencha o nome do titular do cartão</li>');
                    }else if(l.Codigo == 910){
                        $("#dataPortador").addClass("validate_erro");
                        $("#alert-area").append('<li for="dataPortador" generated="true" class="validate_erro">Preencha a data de nascimento do titular do cartão (<i>Ex. 30/11/1980</i></li>');
                    }else if(l.Codigo == 911){
                        $("#telefonePortador").addClass("validate_erro");
                        $("#alert-area").append('<li for="telefonePortador" generated="true" class="validate_erro">Preencha o telefone do titular do cartão (<i>Ex. (11)1111-1111</i>)</li>');
                    }else if(l.Codigo == 912){
                        $("#cpfPortador").addClass("validate_erro");
                        $("#alert-area").append('<li for="cpfPortador" generated="true" class="validate_erro">Preencha o CPF do titular do cartão (<i>Ex. 111.111.111-11</i>)</li>');
                    }else{
                    }
                });
            }else{
                retErrorServer(data);
            }

        }

        retErrorServer = function(data){
            $.ajax({
                type: "POST",
                url: "modules/moip/validation.php?type=log",
                dataType: 'json',
                data: data
            });
        
            alert('Ocorreu um erro ao finalizar seu pagamento.\nTente novamente.');
            window.location.href = 'order.php?step=2';

        }

        disableButton = function(payment){
            $("#" + payment).attr("disabled", "disabled");
            $("#" + payment).removeClass("exclusive");
            $("#" + payment).addClass("exclusive_disabled");
            $("#spinner" + payment).fadeIn();
        }

        enableButton = function(payment){
            $("#" + payment).removeAttr("disabled");
            $("#" + payment).removeClass("exclusive_disabled");
            $("#" + payment).addClass("exclusive");
            $("#spinner" + payment).fadeOut();
        }

        tokenIsEmpty = function(){
            tokenMoip = $("input[name=tokenToMoip]").val();

            if(!tokenMoip){
                moipRequest = $("input[name=moipRequest]").val();
                retErrorServer(moipRequest);
            }else{
                return tokenMoip;
            }
        }

    });