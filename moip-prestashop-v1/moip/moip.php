<?php

/**
 *  @author Vagner Fiuza Vieira - Moip Labs team <integracao@moip.com.br>
 *  @copyright  Moip Pagamentos S.A 2008-2012
 *  @version  Release: 2.0 - Revision: 01
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of Moip Pagamentos S.A
 */
class Moip extends PaymentModule {

    const INSTALL_SQL_FILE = 'install.sql';

    private $_html = '';
    private $_postErrors = array();
    public $currencies;
    public $arrayMoipEnvironment = array(
        "Produção" => 'producao',
        "SandBox" => 'sandbox'
    );
    public $arrayMoipInstallments = array(
        "Aceitar " => true,
        "Não aceitar " => false
    );
    public $arrayMoipAcceptBillet = array(
        "Aceitar " => true,
        "Não aceitar " => false
    );
    public $arrayMoipAcceptDebit = array(
        "Aceitar " => true,
        "Não aceitar " => false
    );
    public $arrayMoipAcceptCreditCard = array(
        "Aceitar " => true,
        "Não aceitar " => false
    );
    public $arrayMoipInstallmentsClient1 = array(
        "Repassar taxa de parcelamento <br>" => true,
        "Juros ao pagador " => false
    );
    public $arrayMoipInstallmentsClient2 = array(
        "Repassar taxa de parcelamento <br>" => true,
        "Juros ao pagador " => false
    );
    public $arrayMoipInstallmentsClient3 = array(
        "Repassar taxa de parcelamento <br>" => true,
        "Juros ao pagador " => false
    );
    public $arrayMoipParcel = array(
        "--- &nbsp;" => 0,
        "2 &nbsp;" => 2,
        "3 &nbsp;" => 3,
        "4 &nbsp;" => 4,
        "5 &nbsp;" => 5,
        "6 &nbsp;" => 6,
        "7 &nbsp;" => 7,
        "8 &nbsp;" => 8,
        "9 &nbsp;" => 9,
        "10 &nbsp;" => 10,
        "11 &nbsp;" => 11,
        "12 &nbsp;" => 12
    );
    public $orderState = array(
        array('c9fecd', '11110', 'Moip - Autorizado', 'payment'),
        array('ffffff', '00100', 'Moip - Iniciado', ''),
        array('fcffcf', '00100', 'Moip - Boleto Impresso', ''),
        array('c9fecd', '00100', 'Moip - Concluido', 'bankwire'),
        array('fec9c9', '11110', 'Moip - Cancelado', 'order_canceled'),
        array('fcffcf', '00100', 'Moip - Em Analise', ''),
        array('ffe0bb', '11100', 'Moip - Estornado', 'refund'),
        array('d6d6d6', '00100', 'Moip - Em Aberto', '')
    );

    function __construct() {
        $this->name = 'moip';
        $this->tab = 'payments_gateways';
        $this->author = 'Moip Labs';

        $this->version = 2.1;

        $this->currencies = true;
        $this->currencies_mode = 'radio';
        // The parent construct is required for translations
        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Moip Pagamentos');
        $this->description = $this->l('O Checkout Transparente é a ferramenta ideal para quem busca aumentar a taxa de conversão de suas vendas,
e dar mais segurança para os seus clientes no momento da compra.');
        $this->confirmUninstall = $this->l('Tem certeza de que deseja desinstalar o Módulo Moip?');
        $this->textButton = $this->l('Efetuar Pagamento');
    }

    /**
     * 	install()<br>
     * 	Executa instalação do módulo.
     *  @return Boolean
     */
    public function install() {

        // SQL Table
        if (!file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE))
            die('lol');
        elseif (!$sql = file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE))
            die('lal');
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query)
            if ($query AND sizeof($query) AND !Db::getInstance()->Execute(trim($query)))
                return false;
        // SQL Table


        $keyNasp = rand(000000, 999999);

        $this->createMoipStates();

        if (
                !parent::install()
                OR !Configuration::updateValue('MOIP_LOGIN', '')
                OR !Configuration::updateValue('MOIP_ENVIRONMENT', true)
                OR !Configuration::updateValue('MOIP_TRANSACTION_ID_PREFIX', 'Seu nome fantasia')
                OR !Configuration::updateValue('MOIP_NASP_KEY', $keyNasp)
                OR !Configuration::updateValue('MOIP_ACCEPT_CREDIT_CARD', true)
                OR !Configuration::updateValue('MOIP_ACCEPT_DEBIT', true)
                OR !Configuration::updateValue('MOIP_ACCEPT_BILLET', true)
                OR !Configuration::updateValue('MOIP_INSTALLMENTS', true)
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_OF_1', '2')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_TO_1', '12')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_1', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_1', true)
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_OF_2', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_TO_2', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_2', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_2', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_OF_3', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_TO_3', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_3', '')
                OR !Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_3', '')
                OR !Configuration::updateValue('MOIP_LOG_ACTIVE', false)
                OR !$this->registerHook('payment')
                OR !$this->registerHook('paymentReturn')
                OR !$this->registerHook('home')
                OR !$this->registerHook('invoice')
                OR !$this->registerHook('rightColumn')
                
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 	uninstall()<br>
     * 	Executa desistalação do módulo.
     *  @return Boolean
     */
    public function uninstall() {
        if (
                !Configuration::deleteByName('MOIP_LOGIN')
                OR !Configuration::deleteByName('MOIP_ENVIRONMENT')
                OR !Configuration::deleteByName('MOIP_TRANSACTION_ID_PREFIX')
                OR !Configuration::deleteByName('MOIP_NASP_KEY')
                OR !Configuration::deleteByName('MOIP_ACCEPT_CREDIT_CARD')
                OR !Configuration::deleteByName('MOIP_ACCEPT_DEBIT')
                OR !Configuration::deleteByName('MOIP_ACCEPT_BILLET')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_OF_1')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_TO_1')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_INTEREST_1')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_CLIENT_1')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_OF_2')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_TO_2')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_INTEREST_2')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_CLIENT_2')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_OF_3')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_TO_3')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_INTEREST_3')
                OR !Configuration::deleteByName('MOIP_INSTALLMENTS_CLIENT_3')
                OR !Configuration::deleteByName('MOIP_STATES')
                OR !Configuration::deleteByName('MOIP_LOG_ACTIVE')
                OR !parent::uninstall())
            return false;
        return true;
    }

    /**
     * 	create_states()<br>
     * 	Cria os STATUS de pagamento Moip na base.
     *  @return Boolean
     */
    public function createMoipStates() {

        /** OBTENDO UMA LISTA DOS IDIOMAS  * */
        $languages = Db::getInstance()->ExecuteS('
		SELECT `id_lang`, `iso_code`
		FROM `' . _DB_PREFIX_ . 'lang`
		');
        /** /OBTENDO UMA LISTA DOS IDIOMAS  * */
        /** INSTALANDO STATUS MOIP * */
        foreach ($this->orderState as $key => $value) {

            if (!Configuration::get('MoIP_STATUS_' . $key)) {

                /** CRIANDO OS STATUS NA TABELA order_state * */
                Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_state`
			( `invoice`, `send_email`, `color`, `unremovable`, `logable`, `delivery`)
				VALUES
			(' . $value[1][0] . ', ' . $value[1][1] . ', \'#' . $value[0] . '\', ' . $value[1][2] . ', ' . $value[1][3] . ', ' . $value[1][4] . ');');

                $this->figura = Db::getInstance()->Insert_ID();

                foreach ($languages as $language_atual) {
                    /** CRIANDO AS DESCRICOES DOS STATUS NA TABELA order_state_lang  * */
                    Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'order_state_lang`
				(`id_order_state`, `id_lang`, `name`, `template`)
					VALUES
				(' . $this->figura . ', ' . $language_atual['id_lang'] . ', \'' . $value[2] . '\', \'' . $value[3] . '\');');
                    /** /CRIANDO AS DESCRICOES DOS STATUS NA TABELA order_state_lang  * */
                }

                /** COPIANDO O ICONE ATUAL * */
                if (file_exists(dirname(__FILE__) . "/icons/$key.gif")) {
                    $file = (dirname(__file__) . "/icons/$key.gif");
                    $newfile = (dirname(dirname(dirname(__file__))) . "/img/os/$this->figura.gif");
                    if (!copy($file, $newfile)) {
                        return false;
                    }
                }
                /** /COPIANDO O ICONE ATUAL * */
                /** GRAVA AS CONFIGURAÇÕES  * */
                Configuration::updateValue('MoIP_STATUS_' . $key, $this->figura);
                Configuration::updateValue('MOIP_STATES', "Created [$key]");
            } else {
                Configuration::updateValue('MOIP_STATES', "Existing [$key]");
            }
        }

        return true;
    }

    /**
     * 	getContent()<br>
     * 	Executa validação e exibição de formulario de módulo Moip ADMIN.
     *  @return HTML
     */
    public function getContent() {

        $this->_html = '<h2>Moip</h2>';
        if (isset($_POST['submitMoIP'])) {
            if (empty($_POST['login']))
                $this->_postErrors[] = $this->l('Digite o login cadastrado no Moip');
            if (empty($_POST['chave_nasp']))
                $this->_postErrors[] = $this->l('A Chave de validação para o NASP deve ser preenchida.');
            if (empty($_POST['prefixo']))
                $this->_postErrors[] = $this->l('O Identificador padrão deve ser preenchido.');

            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('MOIP_LOGIN', $_POST['login']);
                Configuration::updateValue('MOIP_ENVIRONMENT', $_POST['ambiente']);
                Configuration::updateValue('MOIP_TRANSACTION_ID_PREFIX', $_POST['prefixo']);
                Configuration::updateValue('MOIP_NASP_KEY', $_POST['chave_nasp']);
                Configuration::updateValue('MOIP_ACCEPT_BILLET', $_POST['boleto']);
                Configuration::updateValue('MOIP_ACCEPT_DEBIT', $_POST['debito']);
                Configuration::updateValue('MOIP_ACCEPT_CREDIT_CARD', $_POST['cartao_credito']);
                Configuration::updateValue('MOIP_LOG_ACTIVE', $_POST['ativar_log']);
                $this->displayConf();
            }
            else
                $this->displayErrors();
        }
        else if (isset($_POST['submitMoIP_Parcelamento'])) {
            Configuration::updateValue('MOIP_INSTALLMENTS', $_POST['parcelamento']);
            Configuration::updateValue('MOIP_INSTALLMENTS_OF_1', $_POST['parcelamento_de_1']);
            Configuration::updateValue('MOIP_INSTALLMENTS_TO_1', $_POST['parcelamento_ate_1']);
            Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_1', $_POST['parcelamento_juros_1']);
            Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_1', $_POST['assumir_juros_1']);

            Configuration::updateValue('MOIP_INSTALLMENTS_OF_2', $_POST['parcelamento_de_2']);
            Configuration::updateValue('MOIP_INSTALLMENTS_TO_2', $_POST['parcelamento_ate_2']);
            Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_2', $_POST['parcelamento_juros_2']);
            Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_2', $_POST['assumir_juros_2']);

            Configuration::updateValue('MOIP_INSTALLMENTS_OF_3', $_POST['parcelamento_de_3']);
            Configuration::updateValue('MOIP_INSTALLMENTS_TO_3', $_POST['parcelamento_ate_3']);
            Configuration::updateValue('MOIP_INSTALLMENTS_INTEREST_3', $_POST['parcelamento_juros_3']);
            Configuration::updateValue('MOIP_INSTALLMENTS_CLIENT_3', $_POST['assumir_juros_3']);
            $this->displayConf();
        }

        $this->displayMoIP();
        $this->displayFormSettingsMoIP();
        return $this->_html;
    }

    /**
     * 	displayMoIP()<br>
     * 	Exibe informações sobre módulo.
     *  @return HTML
     */
    public function displayMoIP() {

        $KEY_NASP = Configuration::get('MOIP_NASP_KEY');

        $this->_html .= '
		<img src="https://www.moip.com.br/imgs/logo_moip.gif" style="float:left; margin-right:15px;" /><b>
                ' . $this->l('Este módulo permite aceitar pagamentos via Moip.') . '</b><br /><br />
		' . $this->l('Você deverá acessar sua conta Moip e cadastrar a "URL de Notificação" para atualizar os status de pagamento automaticamente em sua loja.') . '<br />
		' . $this->l('Acesse sua conta Moip no menu "Meus dados" >> "Preferências" >> "Notificação das transações", e marque a opção "Receber notificação instantânea de transação".') . '<br />
		' . $this->l('Em "<b>URL de notificação</b>" coloque a seguinte URL: <b>https://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/moip/nasp.php?key=' . htmlentities($KEY_NASP, ENT_COMPAT, 'UTF-8') . '</b><br />') . '
                ' . $this->l('<br /><br /><b>Pronto</b>, sua loja está integrada com o Moip !!!') . '
		<br /><br /><br />';

        $this->_html .= '<script type=\'text/javascript\' src=\'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/moip/script/jquery-1.7.1.min.js\'></script>';

        $this->_html .= '
        <script type=\'text/javascript\'>
            $(window).load(function(){
                $(\'select[name=cartao_credito]\').change(function(){
                        optionCreditCard = $("select[name=cartao_credito]").find(\'option\').filter(\':selected\').val();

                        var form_id = optionCreditCard;
                        $(\'.escolha:visible\').fadeOut();
                        $(\'#\' + form_id).fadeIn();

                });
            });
        </script>';
    }

    /**
     * 	displayFormSettingsMoIP()
     * 	Exibe formulario de configurações do módulo de pagamento.
     *  @return HTML
     */
    public function displayFormSettingsMoIP() {

        $conf = Configuration::getMultiple(array('MOIP_LOGIN',
                    'MOIP_ENVIRONMENT',
                    'MOIP_TRANSACTION_ID_PREFIX',
                    'MOIP_NASP_KEY',
                    'MOIP_ACCEPT_CREDIT_CARD',
                    'MOIP_ACCEPT_DEBIT',
                    'MOIP_ACCEPT_BILLET',
                    'MOIP_INSTALLMENTS',
                    'MOIP_INSTALLMENTS_OF_1',
                    'MOIP_INSTALLMENTS_OF_2',
                    'MOIP_INSTALLMENTS_OF_3',
                    'MOIP_INSTALLMENTS_TO_1',
                    'MOIP_INSTALLMENTS_TO_2',
                    'MOIP_INSTALLMENTS_TO_3',
                    'MOIP_INSTALLMENTS_INTEREST_1',
                    'MOIP_INSTALLMENTS_INTEREST_2',
                    'MOIP_INSTALLMENTS_INTEREST_3',
                    'MOIP_INSTALLMENTS_CLIENT_1',
                    'MOIP_INSTALLMENTS_CLIENT_2',
                    'MOIP_INSTALLMENTS_CLIENT_3',
                    'MOIP_LOG_ACTIVE'));

        $moipLogin = array_key_exists('login', $_POST) ? $_POST['login'] : (array_key_exists('MOIP_LOGIN', $conf) ? $conf['MOIP_LOGIN'] : '');
        $moipEnvironment = array_key_exists('ambiente', $_POST) ? $_POST['ambiente'] : (array_key_exists('MOIP_ENVIRONMENT', $conf) ? $conf['MOIP_ENVIRONMENT'] : '');
        $moipTransactionPrefix = array_key_exists('prefixo', $_POST) ? $_POST['prefixo'] : (array_key_exists('MOIP_TRANSACTION_ID_PREFIX', $conf) ? $conf['MOIP_TRANSACTION_ID_PREFIX'] : '');
        $moipNaspKey = array_key_exists('chave_nasp', $_POST) ? $_POST['chave_nasp'] : (array_key_exists('MOIP_NASP_KEY', $conf) ? $conf['MOIP_NASP_KEY'] : '');
        $moipLogActive = array_key_exists('ativar_log', $_POST) ? $_POST['ativar_log'] : (array_key_exists('MOIP_LOG_ACTIVE', $conf) ? $conf['MOIP_LOG_ACTIVE'] : '');

        $moipAcceptBillet = array_key_exists('boleto', $_POST) ? $_POST['boleto'] : (array_key_exists('MOIP_ACCEPT_BILLET', $conf) ? $conf['MOIP_ACCEPT_BILLET'] : '');
        $moipAcceptDebit = array_key_exists('debito', $_POST) ? $_POST['debito'] : (array_key_exists('MOIP_ACCEPT_DEBIT', $conf) ? $conf['MOIP_ACCEPT_DEBIT'] : '');
        $moipAcceptCreditCard = array_key_exists('cartao_credito', $_POST) ? $_POST['cartao_credito'] : (array_key_exists('MOIP_ACCEPT_CREDIT_CARD', $conf) ? $conf['MOIP_ACCEPT_CREDIT_CARD'] : '');

        $moipInstallments = array_key_exists('parcelamento', $_POST) ? $_POST['parcelamento'] : (array_key_exists('MOIP_INSTALLMENTS', $conf) ? $conf['MOIP_INSTALLMENTS'] : '');
        $moipInstallmentsOf_1 = array_key_exists('parcelamento_de_1', $_POST) ? $_POST['parcelamento_de_1'] : (array_key_exists('MOIP_INSTALLMENTS_OF_1', $conf) ? $conf['MOIP_INSTALLMENTS_OF_1'] : '');
        $moipInstallmentsTo_1 = array_key_exists('parcelamento_ate_1', $_POST) ? $_POST['parcelamento_ate_1'] : (array_key_exists('MOIP_INSTALLMENTS_TO_1', $conf) ? $conf['MOIP_INSTALLMENTS_TO_1'] : '');
        $moipInstallmentsInterest_1 = array_key_exists('parcelamento_juros_1', $_POST) ? $_POST['parcelamento_juros_1'] : (array_key_exists('MOIP_INSTALLMENTS_INTEREST_1', $conf) ? $conf['MOIP_INSTALLMENTS_INTEREST_1'] : '');

        $moipInstallmentsOf_2 = array_key_exists('parcelamento_de_2', $_POST) ? $_POST['parcelamento_de_2'] : (array_key_exists('MOIP_INSTALLMENTS_OF_2', $conf) ? $conf['MOIP_INSTALLMENTS_OF_2'] : '');
        $moipInstallmentsTo_2 = array_key_exists('parcelamento_ate_2', $_POST) ? $_POST['parcelamento_ate_2'] : (array_key_exists('MOIP_INSTALLMENTS_TO_2', $conf) ? $conf['MOIP_INSTALLMENTS_TO_2'] : '');
        $moipInstallmentsInterest_2 = array_key_exists('parcelamento_juros_2', $_POST) ? $_POST['parcelamento_juros_2'] : (array_key_exists('MOIP_INSTALLMENTS_INTEREST_2', $conf) ? $conf['MOIP_INSTALLMENTS_INTEREST_2'] : '');

        $moipInstallmentsOf_3 = array_key_exists('parcelamento_de_3', $_POST) ? $_POST['parcelamento_de_3'] : (array_key_exists('MOIP_INSTALLMENTS_OF_3', $conf) ? $conf['MOIP_INSTALLMENTS_OF_3'] : '');
        $moipInstallmentsTo_3 = array_key_exists('parcelamento_ate_3', $_POST) ? $_POST['parcelamento_ate_3'] : (array_key_exists('MOIP_INSTALLMENTS_TO_3', $conf) ? $conf['MOIP_INSTALLMENTS_TO_3'] : '');
        $moipInstallmentsInterest_3 = array_key_exists('parcelamento_juros_3', $_POST) ? $_POST['parcelamento_juros_3'] : (array_key_exists('MOIP_INSTALLMENTS_INTEREST_3', $conf) ? $conf['MOIP_INSTALLMENTS_INTEREST_3'] : '');

        $moipInstallmentsClient1 = array_key_exists('assumir_juros_1', $_POST) ? $_POST['assumir_juros_1'] : (array_key_exists('MOIP_INSTALLMENTS_CLIENT_1', $conf) ? $conf['MOIP_INSTALLMENTS_CLIENT_1'] : '');
        $moipInstallmentsClient2 = array_key_exists('assumir_juros_2', $_POST) ? $_POST['assumir_juros_2'] : (array_key_exists('MOIP_INSTALLMENTS_CLIENT_2', $conf) ? $conf['MOIP_INSTALLMENTS_CLIENT_2'] : '');
        $moipInstallmentsClient3 = array_key_exists('assumir_juros_3', $_POST) ? $_POST['assumir_juros_3'] : (array_key_exists('MOIP_INSTALLMENTS_CLIENT_3', $conf) ? $conf['MOIP_INSTALLMENTS_CLIENT_3'] : '');

        /** CONFIGURAÇÕES * */
        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Configurações') . '</legend>
			<label>' . $this->l('Login Moip') . ':</label>
			<div class="margin-form"><input type="text" size="33" name="login" value="' . htmlentities($moipLogin, ENT_COMPAT, 'UTF-8') . '" /></div>
			<label>' . $this->l('Identificador padrão') . ':</label>
			<div class="margin-form"><input type="text" size="22" maxlength="22" name="prefixo" value="' . htmlentities($moipTransactionPrefix, ENT_COMPAT, 'UTF-8') . '" /></div>
                        <label>' . $this->l('Chave NASP') . ':</label>
			<div class="margin-form"><input type="text" size="6" maxlength="6" name="chave_nasp" value="' . htmlentities($moipNaspKey, ENT_COMPAT, 'UTF-8') . '" /></div>';

        /** OPÇÃO DE AMBIENTE * */
        $this->_html .= '<label>' . $this->l('Ambiente') . ':</label>';
        $this->_html .= '<div  class="margin-form">' . $this->getInputSelect($this->arrayMoipEnvironment, $moipEnvironment, 'ambiente') . '</div>';
        /** [x] OPÇÃO DE AMBIENTE * */
        /** OPÇÃO CARTÃO DE CRÉDITO * */
        $this->_html .= '<label>' . $this->l('Cartão de crédito') . ':</label>';
        $this->_html .= '<div  class="margin-form">' . $this->getInputSelect($this->arrayMoipAcceptCreditCard, $moipAcceptCreditCard, 'cartao_credito') . '</div>';
        /** [x] OPÇÃO CARTÃO DE CRÉDITO * */
        /** OPÇÃO DÉBITO * */
        $this->_html .= '<label>' . $this->l('Débito Online') . ':</label>';
        $this->_html .= '<div  class="margin-form">' . $this->getInputSelect($this->arrayMoipAcceptDebit, $moipAcceptDebit, 'debito') . '</div>';
        /** [x] OPÇÃO DÉBITO * */
        /** OPÇÃO BOLETO * */
        $this->_html .= '<label>' . $this->l('Boleto') . ':</label>';
        $this->_html .= '<div  class="margin-form">' . $this->getInputSelect($this->arrayMoipAcceptBillet, $moipAcceptBillet, 'boleto') . '</div>';
        /** [x] OPÇÃO BOLETO * */
        /** ATIVAR LOG * */
        if ($moipLogActive) {
            $check = 'checked="checked"';
        }

        $this->_html .= '<label>' . $this->l('Log') . ':</label>';
        $this->_html .= '<div  class="margin-form"><input type="checkbox" ' . $check . ' name="ativar_log" value="1"></div>';
        /** [x] ATIVAR LOG * */
        $this->_html .= '
 		<br /><center><input type="submit" name="submitMoIP" value="' . $this->l('Salvar Configurações') . '" class="button" /></center>
		</fieldset>
		</form>';



        /** PARCELAMENTO  * */
        if (Configuration::get('MOIP_ACCEPT_CREDIT_CARD')) {
            $displayDivCreditCard = 'style="display:display;"';
        } else {
            $displayDivCreditCard = 'style="display:none;"';
        }

        $this->_html .= '<br>
              <div class="escolha" id="1" ' . $displayDivCreditCard . '>
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/coupon.gif" />' . $this->l('Configurações de parcelamentos para Cartão de Crédito') . '</legend>';

        $this->_html .= '<label>' . $this->l('Parcelamento') . ':</label>';
        $this->_html .= '<div class="margin-form">' . $this->getInputSelect($this->arrayMoipInstallments, $moipInstallments, 'parcelamento') . '</div><br><br>';

        $this->_html .= '
               <fieldset>
			<legend><img src="../img/admin/duplicate.gif" />' . $this->l('1° Parcelamento') . '</legend>
                        <label>' . $this->l('De') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsOf_1, 'parcelamento_de_1') . '</div>
			<label>' . $this->l('Até') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsTo_1, 'parcelamento_ate_1') . '</div>
                        <label>' . $this->l('Juros de parcelamento') . ':</label>
			<div class="margin-form">' . $this->getInputRadio($this->arrayMoipInstallmentsClient1, $moipInstallmentsClient1, 'assumir_juros_1') . '
                        de <input type="text" size="5" name="parcelamento_juros_1" value="' . htmlentities($moipInstallmentsInterest_1, ENT_COMPAT, 'UTF-8') . '" />% a.m (<a href="http://pt.wikipedia.org/wiki/Tabela_Price" target="_blank">Tabela Price</a> Ex: 1.99)</div>
            </fieldset>
                    <br>
		<fieldset>
			<legend><img src="../img/admin/duplicate.gif" />' . $this->l('2° Parcelamento') . '</legend>

                        <label>' . $this->l('De') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsOf_2, 'parcelamento_de_2') . '</div>
			<label>' . $this->l('Até') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsTo_2, 'parcelamento_ate_2') . '</div>
                        <label>' . $this->l('Taxa de Juros') . ':</label>
			<div class="margin-form">' . $this->getInputRadio($this->arrayMoipInstallmentsClient2, $moipInstallmentsClient2, 'assumir_juros_2') . '
                        de <input type="text" size="5" name="parcelamento_juros_2" value="' . htmlentities($moipInstallmentsInterest_2, ENT_COMPAT, 'UTF-8') . '" />% a.m (<a href="http://pt.wikipedia.org/wiki/Tabela_Price" target="_blank">Tabela Price</a> Ex: 1.99)</div>
		</fieldset>
                    <br>
 		<fieldset>
			<legend><img src="../img/admin/duplicate.gif" />' . $this->l('3° Parcelamento') . '</legend>
                       <label>' . $this->l('De') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsOf_3, 'parcelamento_de_3') . '</div>
			<label>' . $this->l('Até') . ':</label>
			<div class="margin-form">' . $this->getInputSelect($this->arrayMoipParcel, $moipInstallmentsTo_3, 'parcelamento_ate_3') . '</div>
                        <label>' . $this->l('Taxa de Juros') . ':</label>
			<div class="margin-form">' . $this->getInputRadio($this->arrayMoipInstallmentsClient3, $moipInstallmentsClient3, 'assumir_juros_3') . '
                        de <input type="text" size="5" name="parcelamento_juros_3" value="' . htmlentities($moipInstallmentsInterest_3, ENT_COMPAT, 'UTF-8') . '" />% a.m (<a href="http://pt.wikipedia.org/wiki/Tabela_Price" target="_blank">Tabela Price</a> Ex: 1.99)</div>
 		</fieldset>
                ';

        $this->_html .= '<br /><center><input type="submit" name="submitMoIP_Parcelamento" value="' . $this->l('Salvar Parcelamento') . '"
			class="button" />
		</center>
		</fieldset>
                </div>
		</form>';
        /** [x] PARCELAMENTO * */
    }

    /**
     * 	displayConf()<br>
     * 	Exibe mensagem de confirmação de atualização das configurações.
     * @return HTML
     */
    public function displayConf() {

        $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="' . $this->l('Confirmation') . '" />
			' . $this->l('Configurações atualizadas') . '
		</div>';
    }

    /**
     * 	displayErrors()<br>
     * 	Exibe mensagem de falha ao salvar as configurações..<br>
     * @return HTML
     */
    public function displayErrors() {

        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
		<div class="alert error">
			<h3>' . ($nbErrors > 1 ? $this->l('Existem') : $this->l('Existe')) . ' ' . $nbErrors . ' ' . ($nbErrors > 1 ? $this->l('erros') : $this->l('erro')) . '</h3>
			<ol>';
        foreach ($this->_postErrors AS $error)
            $this->_html .= '<li>' . $error . '</li>';
        $this->_html .= '
			</ol>
		</div>';
    }
    
    /**
     * 	hookRightColumn()<br>
     * 	Adiciona JS Moip ao final da página.<br>
     * @return HTML
     */
    public function hookRightColumn($params) {        
    
        if (Configuration::get('MOIP_ENVIRONMENT') == 'producao'){
            $script_moip = 'https://www.moip.com.br/transparente/MoipWidget-v2.js';
        }else{
            $script_moip = 'https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js';
        }
        $this->context->controller->addJS($this->_path . 'script/jquery.validate.js');
        $this->context->controller->addJS($this->_path . 'script/jquery.maskedinput.js');
        $this->context->controller->addJS($this->_path .'script/moip.js');
        $this->context->controller->addJS($script_moip);
    
        return true;
    }

    
    /**
     * 	hookPayment()<br>
     * 	Exibe módulo como opção de pagamento na pagina (step 3).<br>
     * @param Array $params Parametros GLOBAIS de instância do PrestaShop
     * @return Smarty->display
     * @example payment.tpl
     */
    public function hookPayment($params) {
               if (Configuration::get('MOIP_ENVIRONMENT') == 'producao'){
            $script_moip = 'https://www.moip.com.br/transparente/MoipWidget-v2.js';
        }else{
            $script_moip = 'https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js';
        }
        $this->context->controller->addJS($this->_path . 'script/jquery.validate.js');
        $this->context->controller->addJS($this->_path . 'script/jquery.maskedinput.js');
        $this->context->controller->addJS($this->_path .'script/moip.js');
        $this->context->controller->addJS($script_moip);
        global $smarty, $cookie, $orderTotal, $cart;
        include_once('moipapi.php');
        include_once('log.php');

        $moip = new moipapi();
        $moip_environment = Configuration::get('MOIP_ENVIRONMENT');
        $moip_login = Configuration::get('MOIP_LOGIN');
        $moip->setXmlFile('modules/moip/' . Configuration::get('MOIP_NASP_KEY'));

        $orderValueMoip = $this->getValueMoip();
        $orderValueMoipBr = $this->getValueMoip(true);
        $uniqueIdForMoip = $this->getUniqueMoipId();

        $moipOrder = $this->getOrder($uniqueIdForMoip);

        $moduleCredential = $moip->getModuleCredential($moip_environment);

        $moip->setCredential(array('environment' => $moip_environment,
            'key' => $moduleCredential['key'],
            'token' => $moduleCredential['token']));

        $moip->setPayee($moip_login);

        if ($moipOrder['token_transaction']) {
            $moip->removeInstruction($moipOrder['token_transaction']);
        }

        /** [x ]Address * */
        $customer = new Customer(intval($params['cart']->id_customer));

        $invoiceAddress = new Address(intval($cart->id_address_invoice));

        if ($invoiceAddress->phone) {
            $telefoneAddress = $invoiceAddress->phone;
        } else {
            $telefoneAddress = $invoiceAddress->phone_mobile;
        }

        $telefoneAddress = preg_replace("[^0-9]", "", $telefoneAddress);

        $address = $moip->getCEP($invoiceAddress->postcode);

        if (!$address) {
            $prestashopState = $this->getPrestaShopState($invoiceAddress->id_state);
            $addressUF = $prestashopState['iso_code'];
            $addressStreet = $invoiceAddress->address1;
            $addressNeighborhood = $invoiceAddress->address2;
            $addressCity = $invoiceAddress->city;
            $addressNunber = $this->getNunberAddress($invoiceAddress->address1);
        } else {
            // $addressUF = $address['UF'];
            // $addressStreet = $address['Logradouro'];
            // $addressNeighborhood = $address['Bairro'];
            // $addressCity = $address['Cidade'];
            // $addressNunber = $this->getNunberAddress($invoiceAddress->address1);
            $prestashopState = $this->getPrestaShopState($invoiceAddress->id_state);
            $addressUF = $prestashopState['iso_code'];
            $addressStreet = $invoiceAddress->address1;
            $addressNeighborhood = $invoiceAddress->address2;
            $addressCity = $invoiceAddress->city;
            $addressNunber = $this->getNunberAddress($invoiceAddress->address1);
        }

        $urlReturn = 'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . intval($params['cart']->id) . '&id_module=' . intval($this->id) . '&id_order=' . intval($this->currentOrder) . '&key=' . $customer->secure_key;

        /** [x ]Address * */
        /** API Moip * */
        try {

            $moip->setUniqueID($uniqueIdForMoip);
            $moip->setValue($orderValueMoip);
            //$moip->setReason('Cliente: ' . $customer->email . ' | ' . Configuration::get('PS_SHOP_NAME'));
            $moip->setReason('Cliente: ' . $customer->email . ' | ' . $cart->id .'-' . $Order->id .'');
            $moip->setPayer(array('nome' => $invoiceAddress->firstname . " " . $invoiceAddress->lastname,
                'email' => $customer->email,
                'idpagador' => $customer->email,
                'endereco' => array('logradouro' => $addressStreet,
                    'numero' => $addressNunber,
                    'complemento' => '',
                    'cidade' => $addressCity,
                    'bairro' => $addressNeighborhood,
                    'estado' => $addressUF,
                    'pais' => 'BRA',
                    'cep' => $invoiceAddress->postcode,
                    'telefone' => $telefoneAddress)));
            $moip->setReturnURL($urlReturn);

            if (Configuration::get('MOIP_INSTALLMENTS')) {
                if (Configuration::get('MOIP_INSTALLMENTS_OF_1') != 0 && Configuration::get('MOIP_INSTALLMENTS_TO_1') != 0) {
                    $moip->addParcel(Configuration::get('MOIP_INSTALLMENTS_OF_1'), Configuration::get('MOIP_INSTALLMENTS_TO_1'), Configuration::get('MOIP_INSTALLMENTS_INTEREST_1'), Configuration::get('MOIP_INSTALLMENTS_CLIENT_1'), "AVista");
                }
                if (Configuration::get('MOIP_INSTALLMENTS_OF_2') != 0 && Configuration::get('MOIP_INSTALLMENTS_TO_2') != 0) {
                    $moip->addParcel(Configuration::get('MOIP_INSTALLMENTS_OF_2'), Configuration::get('MOIP_INSTALLMENTS_TO_2'), Configuration::get('MOIP_INSTALLMENTS_INTEREST_2'), Configuration::get('MOIP_INSTALLMENTS_CLIENT_2'), "AVista");
                }
                if (Configuration::get('MOIP_INSTALLMENTS_OF_3') != 0 && Configuration::get('MOIP_INSTALLMENTS_TO_3') != 0) {
                    $moip->addParcel(Configuration::get('MOIP_INSTALLMENTS_OF_3'), Configuration::get('MOIP_INSTALLMENTS_TO_3'), Configuration::get('MOIP_INSTALLMENTS_INTEREST_3'), Configuration::get('MOIP_INSTALLMENTS_CLIENT_3'), "AVista");
                }
            }

            $moip->send();
            $moip_token = $moip->getAnswer()->token;

            $paramsOrder = array('idTransaction' => $uniqueIdForMoip,
                'tokenTransaction' => $moip_token,
                'paymentStatus' => 'EmAberto');
            $moip_request = $this->addOrder($paramsOrder);
        } catch (Exception $e) {
            $moip_request = $moip->getAnswer('xml');
        }


        if (!$moip_token) {
            $moip_request = $moip->getAnswer('xml');
            $payment_error = 'Falha';
            $payment_error_message = $moip->getAnswer()->mensagem;
        }


        /** [x] API Moip * */
        $smarty->assign(array(
            'credito' => Configuration::get('MOIP_ACCEPT_CREDIT_CARD'),
            'debito' => Configuration::get('MOIP_ACCEPT_DEBIT'),
            'boleto' => Configuration::get('MOIP_ACCEPT_BILLET'),
            'moip_token' => $moip_token,
            'orderValue' => $orderValueMoip,
            'orderValueBr' => $orderValueMoipBr,
            'uniqueIdForMoip' => $uniqueIdForMoip,
            'this_path' => $this->_path,
            'idCart' => $cart->id,
            'moipRequest' => $moip_request,
            'environment' => $moip_environment,
            'payment_error' => $payment_error,
            'payment_error_message' => $payment_error_message,
            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . "modules/{$this->name}/"));

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * 	hookPaymentReturn()<br>
     * 	Executa display de template na pagina de confirmação de pagamento<br>
     * @param Array $vars Array de parametros a serem exibidos na pagina de confirmação de pagamento
     * @return Smarty->display
     * @example payment_return.tpl
     */
    public function hookPaymentReturn($vars) {
        global $smarty, $cart;
        include_once('moipapi.php');
        include_once ('log.php');

        $moipApi = new moipapi();

        $log = new log(Configuration::get('MOIP_LOG_ACTIVE'));
        $log->setLogDir(Configuration::get('MOIP_NASP_KEY'));

        $log->write("Logando Return: ", $vars);

        extract($vars);

        $paymentValue = $this->getValueMoip(true, $payment_value);

        $products = $cart->getProducts();
        $prod = array();
        $i = 0;
        foreach ($products as $product) 
        {
               $prod[$i]["id"] = $product["id_product"];
               $prod[$i]["aid"] = $product["id_product_attribute"];
               $prod[$i]["qty"] = $product["cart_quantity"];
               $prod[$i]["name"] = $product["name"];
               $prod[$i]["price"] = $product["price"];
               $i++;
        }

        $smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'payment_form_institution' => $payment_form_institution,
            'payment_form' => $payment_form,
            'payment_url' => $payment_url,
            'payment_status' => $payment_status,
            'payment_code' => $payment_code,
            'payment_value' => (float)($cart->getOrderTotal(true, Cart::BOTH)),
            'order_total' => (float)($cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING)),
            'shipping' => (float)($cart->getOrderTotal(true, Cart::ONLY_SHIPPING)),
            'items' => (float)($cart->nbProducts()),
            'cart_id' => (float)($cart->id),
            'cart_content' => $prod
        ));

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     * 	hookInvoice()<br>
     * 	Executa display de template nos detalhes do Pedido PrestaShop "INVOICE"<br>
     * @param Array $params Parametros GLOBAIS de instância do PrestaShop
     * @return Smarty->display
     * @example invoice.tpl
     */
    public function hookInvoice($params) {
        $id_order = $params['id_order'];

        global $smarty;

        $transactionData = $this->getOrderData($id_order);
        extract($transactionData);
        $orderData = $this->getOrderFromMoip($id_order);
        if ($orderData) {
            extract($orderData);
            $orderPaymentMoip = true;
        }

        $paymentStatus = $this->getStatusMoipArray($payment_status);

        $smarty->assign(array(
            'paymentFormText' => $payment_form,
            'paymentFormInstitutionText' => $payment_form_institution,
            'paymentStatusText' => $paymentStatus['string'],
            'paymentStatusFinal' => $paymentStatus['final'],
            'paymentCode' => $payment_code,
            'tokenTransaction' => $token_transaction,
            'orderPaymentMoip' => $orderPaymentMoip,
            'paymentValue' => $paymentValue,
            'this_page' => $_SERVER['REQUEST_URI'],
            'this_path' => $this->_path,
            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . "modules/{$this->name}/"));
        return $this->display(__FILE__, 'invoice.tpl');
    }

    /**
     * 	getInputRadio()<br>
     * 	Gera input RADIO com dados de array<br>
     * @param Array $arrayParam Array de parametros a serem criados no RADIO
     * @param String $varParam Valor selecionado 'checked'
     * @param String $varName Tag 'name' do SELECT     *
     * @return HTML
     */
    public function getInputRadio($arrayParam, $varParam, $varName) {

        foreach ($arrayParam as $id => $value) {

            if ($varParam == $value) {
                $check = 'checked="checked"';
            } else {
                $check = '';
            }

            $inputRadio .= '<input type="radio" name="' . $varName . '" value="' . $value . '" ' . $check . ' >' . $id . '';
        }

        return $inputRadio;
    }

    /**
     * 	getInputSelect()<br>
     * 	Gera input SELECT com dados de array<br>
     * @param Array $arrayParam Array de parametros a serem criados no SELECT
     * @param String $varParam Valor selecionado 'selected'
     * @param String $varName Tag 'name' do SELECT     *
     * @return HTML
     */
    public function getInputSelect($arrayParam, $varParam, $varName) {

        $inputSelect = '<select name="' . $varName . '">';

        foreach ($arrayParam as $id => $value) {
            if ($varParam == $value) {
                $check = 'selected';
            } else {
                $check = '';
            }

            $inputSelect .= '<option value="' . $value . '" ' . $check . '>' . $id . '</option>';
        }
        $inputSelect .= '</select>';

        return $inputSelect;
    }

    /**
     * 	getNunberAddress()<br>
     * 	Ferramenta para recuperar numero do endereço após uma virgula "," digitado no endereço/rua.<br>
     * @param String $lagradouro Id de carrinho PrestaShop
     * @return Number
     */
    public function getNunberAddress($lagradouro) {

        $numero = explode(',', $lagradouro);
        $numero = preg_replace("[^0-9]", "", $numero[1]);

        if ($numero != "") {
            return $numero;
        } else {
            return "0";
        }
    }

    /**
     * 	getPrestaShopState()
     * 	Retorna ESTADO e UF do PrestaShop.
     *  @param int  $id_state Referente ao ID de estado na base defalt prestashop
     *  @return array name: São Paulo, iso_code:SP
     */
    public function getPrestaShopState($id_state) {
        $rq = Db::getInstance()->getRow('
		SELECT `name`, `iso_code` FROM `' . _DB_PREFIX_ . 'state`
		WHERE id_state = \'' . pSQL($id_state) . '\'');
        return $rq;
    }

    /**
     * 	validateOrder()<br>
     * 	Valida e gera Order de pidido PrestaShop.<br>
     * @param Int $idCart Id de carrinho PrestaShop
     * @param Int $idOrderState Novo status de pagamento
     * @param Float $amountPaid Valor pago
     * @param String $message Mensagem privada a ser exibida a nos detalhes do pedido
     */
    public function validateOrder($idCart, $idOrderState = false, $amountPaid = false, $message = NULL) {
        include_once('log.php');
        $log = new log(Configuration::get('MOIP_LOG_ACTIVE'));
        $log->setLogDir(Configuration::get('MOIP_NASP_KEY'));

        $log->write("Gera pedido: Cart[" . $idCart . "]");

        if (!$this->active)
            return;
        if ($idOrderState == false) {
            $idOrderState = Configuration::get('MoIP_STATUS_4');
        }

        $currency = $this->getCurrency();
        $cart = new Cart(intval($idCart));
        $cart->id_currency = $currency->id;
        $cart->save();

        
/*         $amountPaid = 1233; */

        if ($amountPaid == false)
            $amountPaid = $this->getValueMoip();

        parent::validateOrder($idCart, $idOrderState, $amountPaid, $this->displayName, $message, $mailVars, $currency->id, false, $cart->secure_key);

        if ($amountPaid != $this->getValueMoip()) {

            $log->write("Valor inicial:[" . $this->getValueMoip() . "] Pago: [" . $amountPaid . "]");
            
            $extraVars = array();
            $order = new Order($this->currentOrder);
            $history = new OrderHistory();
            $history->id_order = intval($order->id);
            $history->changeIdOrderState(intval($idOrderState), intval($order->id));
            $history->addWithemail(true, $extraVars);
        }
    }

    /**
     * 	newHistory()<br>
     * 	Atualiza status no PrestaShop.<br>
     * @param String $uniqueIdForMoip Id próprio enviado ao Moip
     * @param String $paymentStatus Novo status de pagamento
     * @param Int $idOrder Id Order PrestaShop
     */
    public function newHistory($uniqueIdForMoip, $paymentStatus) {
        include_once('log.php');
        $log = new log(Configuration::get('MOIP_LOG_ACTIVE'));
        $log->setLogDir(Configuration::get('MOIP_NASP_KEY'));

        if (isset($uniqueIdForMoip)) {

            $orderData = $this->getOrder($uniqueIdForMoip);

            extract($orderData);
            $paymentStatus = $this->getStatusMoipArray($paymentStatus);

            $extraVars = array();
            $order = new Order($id_order);
            $history = new OrderHistory();
            $history->id_order = intval($order->id);
              //igor
            if(intval($paymentStatus['configId']) == 25){
                $history->changeIdOrderState(2, intval($order->id)); //pagamento aceito       
            }
            elseif (intval($paymentStatus['configId']) == 28) {
                    //do nothing - useless state
                }    
            else{
                $history->changeIdOrderState(intval($paymentStatus['configId']), intval($order->id));
            }
            //fim igor
            $history->addWithemail(true, $extraVars);
            $log->write("Status atualizado Order: [" . $order->id . "]");
        }
    }

    /**
     * 	addOrder()<br>
     * 	Adiciona e atualiza dados de order prestashop e nasp em tabela Moip.
     * @param Array $params Dados para inclusão na tabela.
     * @return Boolean
     * */
    public function addOrder($params) {
        global $cart;

        extract($params);

        if (!$paymentStatus)
            $paymentStatus = '2';
        $paymentStatus = $this->getStatusMoipArray($paymentStatus);

        if ($idTransaction && $tokenTransaction) {
            $execQuery = Db::getInstance()->Execute("
		INSERT INTO `" . _DB_PREFIX_ . "moip_order` (`id_order`,
                    `id_cart`,
                    `id_transaction`,
                    `token_transaction`,
                    `payment_url`,
                    `payment_form`,
                    `payment_form_institution`,
                    `payment_code`,
                    `payment_status`,
                    `payment_value`,
                    `payment_classification`)                    
		VALUES(" . intval($this->currentOrder) . ",
                    " . intval($cart->id) . ",
                    '" . pSQL($idTransaction) . "',
                    '" . pSQL($tokenTransaction) . "',
                    '" . pSQL($paymentURL) . "',
                    '" . pSQL($paymentForm) . "',
                    '" . pSQL($paymentFormInstitution) . "',
                    '" . pSQL($paymentCode) . "',
                    '" . pSQL($paymentStatus['id']) . "',
                    '" . pSQL($paymentValue) . "',
                    '" . pSQL($paymentClassification) . "')");

            if ($execQuery) {

                return "INSERT OK";
            } else {

                $execQueryUpdateAll = Db::getInstance()->Execute("
                UPDATE `" . _DB_PREFIX_ . "moip_order` SET `id_order`='" . intval($this->currentOrder) . "',
                `id_cart`='" . intval($cart->id) . "',
                `token_transaction`='" . pSQL($tokenTransaction) . "',
                `payment_url`='" . pSQL($paymentURL) . "',
                `payment_form`='" . pSQL($paymentForm) . "',
                `payment_form_institution`='" . pSQL($paymentFormInstitution) . "',
                `payment_code`='" . pSQL($paymentCode) . "',
                `payment_status`='" . pSQL($paymentStatus['id']) . "',
                `payment_value`='" . pSQL($paymentValue) . "',
                `payment_classification`='" . pSQL($paymentClassification) . "'
                    WHERE
                `id_transaction`='" . $idTransaction . "';");

                if ($execQueryUpdateAll) {
                    return "UPDATE OK";
                } else {
                    return "UPDATE FAIL";
                }
            }
        } else if ($type == 'update') {
            $execQuery = Db::getInstance()->Execute("
                UPDATE `" . _DB_PREFIX_ . "moip_order` SET `payment_code`='" . $paymentCode . "',
                `payment_status`='" . $paymentStatus['id'] . "',
                `payment_value`='" . $paymentValue . "',
                `payment_form`='" . $paymentForm . "'
                WHERE
                `id_transaction`='" . $idTransaction . "';");
            if ($execQuery)
                return "PARTIAL UPDATE OK";
            else
                return "PARTIAL UPDATE FAIL";
        }else {
            return "INSERT FAIL";
        }
    }

    /**
     * 	getOrder()<br>
     * 	Recupera ID Order gerado pelo PrestaShop.
     * @param String $id_transaction Identificador de ID único enviado ao Moip
     * @return array()
     *
     * */
    public function getOrder($id_transaction) {
        $rq = Db::getInstance()->getRow('
		SELECT * FROM `' . _DB_PREFIX_ . 'moip_order`
		WHERE id_transaction = \'' . pSQL($id_transaction) . '\'');
        return $rq;
    }

    /**
     * 	getOrderData()<br>
     * 	Recupera todos os dados de 'moip_order'.
     * @param Int $id_order Identificador numerico de ordem PrestaShop
     * @return array()
     */
    public function getOrderData($id_order) {
        $rq = Db::getInstance()->getRow('
		SELECT * FROM `' . _DB_PREFIX_ . 'moip_order`
		WHERE id_order = \'' . pSQL($id_order) . '\'');
        return $rq;
    }

    /**
     * 	getOrderFromMoip()<br>
     * 	Recupera dados de peido caso tenha sido realizado pelo Moip.
     * @param Int $id_order Identificador numerico de ordem PrestaShop
     * @return array()
     */
    public function getOrderFromMoip($id_order) {
        $rq = Db::getInstance()->getRow('
                SELECT * FROM `' . _DB_PREFIX_ . 'orders`
		WHERE `module` = \'' . pSQL($this->name) . '\' and `id_order` = \'' . pSQL($id_order) . '\'');
        return $rq;
    }

    /**
     * 	getOrderCart()<br>
     * 	Retorna ID Order relacionado ao ID de Carrinho.<br>
     * @param String $id_cart Identificador numerico de carrinho PrestaShop
     * @return array() Ex:"array('id_order' => '1000')"
     */
    public function getOrderCart($id_cart) {
        $rq = Db::getInstance()->getRow('
		SELECT `id_order` FROM `' . _DB_PREFIX_ . 'moip_order`
		WHERE id_cart = \'' . pSQL($id_cart) . '\'');
        return $rq;
    }

    /**
     * 	getStatusMoipArray()<br>
     * 	Retorna tipos de status Moip.<br>
     * @param String $statusMoip Status de pagamento Moip (ID ou String)
     * @return Array('id','string','final','configId','configString').
     * <b>id:</b> Id Moip (ex: <b>5</b>)<br>
     * <b>string:</b> Id Moip em String (ex: <b>'Cancelado'</b>)<br>
     * <b>final:</b> Boleano indicando se status é um status final ou não (ex: <b>true</b>)<br>
     * <b>configId:</b> Id de status PrestaShop (ex: <b>18</b>)<br>
     * <b>configString:</b> String gerada na instalação do Módulo referenciando o id status PrestaShop (Ex: <b>'MoIP_STATUS_4'</b>)
     */
    public function getStatusMoipArray($statusMoip) {

        if ($statusMoip == 1 || $statusMoip == "Autorizado")
            $status = array('id' => '1',
                'string' => 'Autorizado',
                'final' => true,
                'configId' => Configuration::get('MoIP_STATUS_0'),
                'configString' => 'MoIP_STATUS_0');
        elseif ($statusMoip == 2 || $statusMoip == "Iniciado")
            $status = array('id' => '2',
                'string' => 'Iniciado',
                'final' => false,
                'configId' => Configuration::get('MoIP_STATUS_1'),
                'configString' => 'MoIP_STATUS_1');
        elseif ($statusMoip == 3 || $statusMoip == "BoletoImpresso")
            $status = array('id' => '3',
                'string' => 'Boleto Impresso',
                'final' => false,
                'configId' => Configuration::get('MoIP_STATUS_2'),
                'configString' => 'MoIP_STATUS_2');
        elseif ($statusMoip == 4 || $statusMoip == "Concluido")
            $status = array('id' => '4',
                'string' => 'Concluido',
                'final' => true,
                'configId' => Configuration::get('MoIP_STATUS_3'),
                'configString' => 'MoIP_STATUS_3');
        elseif ($statusMoip == 5 || $statusMoip == "Cancelado")
            $status = array('id' => '5',
                'string' => 'Cancelado',
                'final' => true,
                'configId' => Configuration::get('MoIP_STATUS_4'),
                'configString' => 'MoIP_STATUS_4');
        elseif ($statusMoip == 6 || $statusMoip == "EmAnalise")
            $status = array('id' => '6',
                'string' => 'Em Análise',
                'final' => false,
                'configId' => Configuration::get('MoIP_STATUS_5'),
                'configString' => 'MoIP_STATUS_5');
        elseif ($statusMoip == 7 || $statusMoip == "Estornado")
            $status = array('id' => '7',
                'string' => 'Estornado',
                'final' => true,
                'configId' => Configuration::get('MoIP_STATUS_6'),
                'configString' => 'MoIP_STATUS_6');
        elseif ($statusMoip == 0 || $statusMoip == null || $statusMoip == 'EmAberto')
            $status = array('id' => '0',
                'string' => 'Em Aberto',
                'final' => false,
                'configId' => Configuration::get('MoIP_STATUS_7'),
                'configString' => 'MoIP_STATUS_7');

        return $status;
    }

    /**
     * 	getL()<br>
     * 	Retorna mensagens.<br>
     * @param String $key Chave de mensagem a ser retornada
     * @param String $params Parametros adicionais da mensagem
     * @return String Ex:"Valor nao especificado corretamente"
     */
    public function getL($key, $params = null) {

        extract($params);

        $translations = array(
            'valor_moip' => $this->l('Valor nao especificado corretamente.'),
            'status_pagamento_moip' => $this->l('Status do Pagamento nao defifido corretamente, ou invalido.'),
            'payment' => $this->l('Moip Pagamentos '),
            'id_transacao_moip' => $this->l('ID Proprio invalido ou nao relacionado a uma ordem de pagamento'),
            'email_consumidor_moip' => $this->l('E-Mail do cliente nao informado, POST invalido.'),
            'post_cod_moip' => $this->l('Codigo MoIP nao informado corretamente, ATENCAO ESSE POST PODE SER FRAUDOLENTO.'),
            'cart' => $this->l('Carrinho não validado.'),
            'order' => $this->l('Transacao ja processada anteriormente com esse carrinho.'),
            'adminMessage' => $this->l('Pagamento processado por Moip Pagamentos<br />Aguarde confirmação de pagamento.<br /><br />'),
            'adminMessageCard' => $this->l('Pagamento processado por Moip Pagamentos<br />Codigo Moip: <b><u>' . $paymentCode . '</b></u><br />Token Moip: ' . $tokenTransaction . '<br /><br />'),
            'adminMessageFail' => $this->l('Transação não processada<br />Carrinho de compras zerado<br />Cliente: <b >' . $customerName . '</b ><br /><br />'),
            'verified' => $this->l('Transação Moip não VERIFICADA.'),
            'mail' => $this->l('Envio de email de notificação.'),
        );
        return $translations[$key];
    }

    /**
     * 	getUniqueMoipId()<br>
     * 	Retorna ID Próprio do pedido PrestaShop para validar instrução junto ao Moip<br>
     * @return String.
     */
    public function getUniqueMoipId() {
        global $cart;

        if (Configuration::get('MOIP_TRANSACTION_ID_PREFIX') == '{timestamp}') {
            $moip_transaction_id_prefix = date('Y.m.d.H.i.s');
        } else {
            $moip_transaction_id_prefix = Configuration::get('MOIP_TRANSACTION_ID_PREFIX');
        }

        $uniqueIdForMoip = $moip_transaction_id_prefix . ' [' . $cart->id . ']';

        return $uniqueIdForMoip;
    }

    /**
     * 	getUniqueMoipId()
     * 	Retorna ID Próprio do pedido PrestaShop para validar instrução junto ao Moip.
     *  @param Boolean  $formatBr Referente ao tipo de valor retornado, com "." para false e "," para true
     *  @param String $value Valor especifico a ser retornado, em caso de null, retorna valor do carrinho PrestaShop.
     *  @return Float Ex: "100.00" ou "100,00"
     */
    public function getValueMoip($formatBr = false, $value = null) {
        global $cart;
        $currency = $this->getCurrency();

        $orderValue =  (float)($cart->getOrderTotal(true, Cart::BOTH));
        if ($value != null)
            $orderValue = $value;

        if ($formatBr) {
            $amountPaid = number_format($orderValue, 2, ',', '');
        } else {
            $amountPaid = number_format($orderValue, 2, '.', '');
        }


        return $amountPaid;
    }

}

?>
