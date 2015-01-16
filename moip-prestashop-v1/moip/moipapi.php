<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of moipapi
 *
 * @author Vagner
 */
class moipapi {

    /**
     * Associative array with two keys. 'key'=>'your_key','token'=>'your_token'
     *
     * @var array
     * @access private
     */
    private $credential;
    /**
     * Define the payment's reason
     *
     * @var string
     * @access private
     */
    private $reason;
    /**
     * The application's environment
     *
     * @var string
     * @access private
     */
    private $environment;
    /**
     * Transaction's unique ID
     *
     * @var string
     * @access private
     */
    private $uniqueID;
    /**
     * Associative array of payment's way
     *
     * @var array
     * @access private
     */
    private $payment_ways = array('boleto' => 'BoletoBancario',
        'financiamento' => 'FinanciamentoBancario',
        'debito' => 'DebitoBancario',
        'cartao_credito' => 'CartaoCredito',
        'cartao_debito' => 'CartaoDebito',
        'carteira_moip' => 'CarteiraMoIP');
    /**
     * Associative array of payment's institutions
     *
     * @var array
     * @access private
     */
    private $institution = array('moip' => 'MoIP',
        'visa' => 'Visa',
        'american_express' => 'AmericanExpress',
        'mastercard' => 'Mastercard',
        'diners' => 'Diners',
        'banco_brasil' => 'BancoDoBrasil',
        'bradesco' => 'Bradesco',
        'itau' => 'Itau',
        'real' => 'BancoReal',
        'unibanco' => 'Unibanco',
        'aura' => 'Aura',
        'hipercard' => 'Hipercard',
        'paggo' => 'Paggo', //oi paggo
        'banrisul' => 'Banrisul'
    );
    /**
     * Payment method
     *
     * @var array
     * @access private
     */
    private $payment_method;
    /**
     * Arguments of payment method
     *
     * @var array
     * @access private
     */
    private $payment_method_args;
    /**
     * Payment's type
     *
     * @var string
     * @access private
     */
    private $payment_type;
    /**
     * Associative array with payer's information
     *
     * @var array
     * @access private
     */
    private $payer;
    /**
     * Payee
     *
     * @var string
     * @access private
     */
    private $payee;
    /**
     * Server's answer
     *
     * @var object
     * @access public
     */
    public $answer;
    /**
     * The transaction's value
     *
     * @var numeric
     * @access private
     */
    private $value;
    /**
     * URL to customer feedback
     *
     * @var object
     * @access private
     */
    private $url_return;
    /**
     * Simple XML object
     *
     * @var object
     * @access private
     */
    private $xml;
    /**
     * Simple XML object
     *
     * @var object
     * @access private
     */
    private $xmlSent;
    /**
     * Simple XML object
     *
     * @var object
     * @access private
     */
    private $xmlPayment;
    /**
     * Array data transaction
     *
     * @var object
     * @access private
     */
    private $paymentData;

    /**
     * Array XML File
     *
     * @var object
     * @access private
     */
    private $xmlFile;

    /**
     * Method construct
     *
     * @return void
     * @access public
     */
    function __construct() {

        /* @var $environment <environment> */
        //setEnvironment($credential['environment']);
        //$this->environment = $credential['environment'];
        //Verify the environment variable, if not 'producao' set 'sandbox'
        if ($this->environment != 'producao') {
            $this->environment = 'sandbox';
        }

        $this->initXMLObject();
    }

    /**
     * Method initXMLObject()
     *
     * Start a new XML structure for the requests
     *
     * @return void
     * @access private
     */
    private function initXMLObject() {
        $this->xml = new SimpleXmlElement('<?xml version="1.0" encoding="utf-8" ?><EnviarInstrucao></EnviarInstrucao>');
        $this->xml->addChild('InstrucaoUnica')
                ->addAttribute('TipoValidacao', 'Transparente');
    }

    /**
     * Method setEnvironment()
     *
     * Define the environment for the API utilization.
     *
     * @param string $environment Only two values supported, 'sandbox' or 'producao'
     */
    public function setEnvironment($environment) {
        if ($environment != 'sandbox' and $environment != 'producao')
            throw new InvalidArgumentException("Ambiente invÃ¡lido");

        $this->environment = $environment;
        return $this;
    }

    /**
     * Method setCredential()
     *
     * Set the credentials(key,token) required for the API authentication.
     *
     * @param array $credential Array with the credentials token and key
     * @return void
     * @access public
     */
    public function setCredential($credential) {
        if (!isset($credential['token']) or
                !isset($credential['key']) or
                strlen($credential['token']) != 32 or
                strlen($credential['key']) != 40)
            throw new InvalidArgumentException("credential invÃ¡lida");

        $this->credential = $credential;
        $this->setEnvironment($credential['environment']);
        return $this;
    }

    /**
     * Method validate()
     *
     * Make the data validation
     *
     * @return void
     * @access public
     */
    public function validate() {
        if (!isset($this->credential) or
                !isset($this->reason) or
                !isset($this->uniqueID))
            throw new InvalidArgumentException("Dados requeridos nÃ£o preenchidos. VocÃª deve especificar as credenciais, a razÃ£o do pagamento e seu ID prÃ³prio");

        return $this;
    }

    /**
     * Method setUniqueID()
     *
     * Set the unique ID for the transaction
     *
     * @param numeric $id Unique ID for each transaction
     * @return void
     * @access public
     */
    public function setUniqueID($id) {
        $this->uniqueID = $id;
        return $this;
    }

    /**
     * Method setReason()
     *
     * Set the short description of transaction. eg. Order Number.
     *
     * @param string $reason The reason fo transaction
     * @return void
     * @access public
     */
    public function setReason($reason) {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Method setPayer()
     *
     * Set contacts informations for the payer.
     *
     * @param array $payer Contact information for the payer.
     * @return voi
     * @access public
     */
    public function setPayer($payer) {
        $this->payer = $payer;
        return $this;
    }

    /**
     * Method setPayee()
     *
     * Set login Moip payee.
     *
     * @param string $payee.
     * @return voi
     * @access public
     */
    public function setPayee($payee) {
        $this->payee = $payee;
        return $this;
    }

    /**
     * Method setValue()
     *
     * Set the transaction's value
     *
     * @param numeric $value The transaction's value
     * @return void
     * @access public
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    /**
     * Method addMessage()
     *
     * Add a message in the instruction to be displayed to the payer.
     *
     * @param string $msg Message to be displayed.
     * @return void
     * @access public
     */
    public function addMessage($msg) {
        if (!isset($this->xml->InstrucaoUnica->Mensagens)) {
            $this->xml->InstrucaoUnica->addChild('Mensagens');
        }

        $this->xml->InstrucaoUnica->Mensagens->addChild('Mensagem', $msg);
        return $this;
    }

    /**
     * Method setReturnURL()
     *
     * Set the return URL, which redirects the client after payment.
     *
     * @param string $url Return URL
     * @access public
     */
    public function setReturnURL($url) {
        $this->url_return = htmlentities($url);
        return $this;
    }

    /**
     * Method setXmlFile()
     *
     * Set the param save asXML.
     *
     * @param array $file Archive file
     * @access public
     */
    public function setXmlFile($file) {
        $this->xmlFile = $file;
        return $this;
    }


    /**
     * Method setXmlFile()
     *
     * Set the param save asXML.
     *
     * @param array $file Archive file
     * @access public
     */
    public function getXmlFile() {
        return $this->xmlFile;
    }    /**
     * Method addParcel()
     *
     * Allows to add a order to parceling.
     *
     * @param numeric $min The minimum number of parcels.
     * @param numeric $max The maximum number of parcels.
     * @param numeric $rate The percentual value of rates
     * @return void
     * @access public
     */
    public function addParcel($min, $max, $rate='', $anticipation = false, $receipt="AVista") {
        if (!isset($this->xml->InstrucaoUnica->Parcelamentos)) {
            $this->xml->InstrucaoUnica->addChild('Parcelamentos');
        }

        $parcela = $this->xml->InstrucaoUnica->Parcelamentos->addChild('Parcelamento');
        $parcela->addChild('MinimoParcelas', $min);
        $parcela->addChild('MaximoParcelas', $max);
        $parcela->addChild('Recebimento', 'AVista');
        
        if ($anticipation == true) 
        {
        /* $parcela->addChild('Repassar', true); */
        }
        else if ($rate != null) 
        {
        /* $parcela->addChild('Recebimento', $receipt); */
            $parcela->addChild('Juros', $rate);
        }

        return $this;
    }

    /**
     * Method getXML()
     *
     * Returns the XML that is generated. Useful for debugging.
     *
     * @return string
     * @access public
     */
    public function getXML() {
        $rand = rand('9999999','99999999999999');
        $this->xml->InstrucaoUnica->addChild('IdProprio', $rand.$this->payee.'_'.$this->uniqueID);
        $this->xml->InstrucaoUnica->addChild('Razao', $this->reason);

        if (empty($this->value))
            throw new InvalidArgumentException('Erro: o valor da transaÃ§Ã£o deve ser especificado');

        $this->xml->InstrucaoUnica->addChild('Valores')
                ->addChild('Valor', $this->value)
                ->addAttribute('moeda', 'BRL');


        if (!empty($this->payer)) {
            $p = $this->payer;
            $this->xml->InstrucaoUnica->addChild('Pagador');
            (isset($p['nome'])) ? $this->xml->InstrucaoUnica->Pagador->addChild('Nome', $this->payer['nome']) : null;
            (isset($p['email'])) ? $this->xml->InstrucaoUnica->Pagador->addChild('Email', $this->payer['email']) : null;
            (isset($p['identidade'])) ? $this->xml->InstrucaoUnica->Pagador->addChild('Identidade', $this->payer['identidade']) : null;
            (isset($p['idpagador'])) ? $this->xml->InstrucaoUnica->Pagador->addChild('IdPagador', $this->payer['idpagador']) : null;

            $p = $this->payer['endereco'];
            $this->xml->InstrucaoUnica->Pagador->addChild('EnderecoCobranca');
            (isset($p['logradouro'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Logradouro', $this->payer['endereco']['logradouro']) : null;
            (isset($p['numero'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Numero', $this->payer['endereco']['numero']) : null;
            (isset($p['complemento'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Complemento', $this->payer['endereco']['complemento']) : null;
            (isset($p['bairro'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Bairro', $this->payer['endereco']['bairro']) : null;
            (isset($p['cidade'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Cidade', $this->payer['endereco']['cidade']) : null;
            (isset($p['estado'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Estado', $this->payer['endereco']['estado']) : null;
            (isset($p['pais'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('Pais', $this->payer['endereco']['pais']) : null;
            (isset($p['cep'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('CEP', $this->payer['endereco']['cep']) : null;
            (isset($p['telefone'])) ? $this->xml->InstrucaoUnica->Pagador->EnderecoCobranca->addChild('TelefoneFixo', $this->payer['endereco']['telefone']) : null;
        }

        $this->xml->InstrucaoUnica->addChild('Recebedor')
                                   ->addChild('LoginMoIP', $this->payee);

        $this->xml->InstrucaoUnica->addChild('URLRetorno', $this->url_return);

        $return = $this->xml->asXML();
        $this->initXMLObject();
        return str_ireplace("\n", "", $return);
    }

    /**
     * Method sendCurl()
     *
     * Create instance for sending data through the url.
     *
     * @return string
     * @access public
     */
    public function sendCurl($credentials, $xml, $url='https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica', $method='POST') {
        $header[] = "Authorization: Basic " . base64_encode($credentials);
        if (!function_exists('curl_init'))
            return $this->send_without_curl($credentials, $xml, $url);

        $ch = curl_init();
        $options = array(CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_RETURNTRANSFER => true
        );

        curl_setopt_array($ch, $options);
        $ret = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $this->xmlSent = $xml;
        $xml = new SimpleXmlElement($xml);
        $xmlFile = $this->getXmlFile();
        $xml->asXML($xmlFile.'/request.xml');

        return (object) array('resposta' => $ret, 'erro' => $err);
    }

    /**
     * Method sendCurl()
     *
     * Retrieves XML and sends.
     *
     * @return string
     * @access public
     */
    public function send($client=null) {

        $moduleCredential = $this->getModuleCredential($this->environment);
        $url = $moduleCredential['base'] . 'ws/alpha/EnviarInstrucao/Unica';

        $this->answer = $this->sendCurl($this->credential['token'] . ':' . $this->credential['key'],
                        $this->getXML(),
                        $url);

        return $this;
    }

    /**
     * Method getAnswer()
     *
     * Gets the server's answer
     *
     * @return object
     * @access public
     */
    public function getAnswer($formato=null) {
        if ($formato == "xml") {

            return $this->answer->resposta;
        }

        $xml = new SimpleXmlElement($this->answer->resposta);
        $xmlFile = $this->getXmlFile();
        $xml->asXML($xmlFile.'/response.xml');

        if (isset($xml->Resposta->Erro)) {
            return (object) array('sucesso' => false, 'mensagem' => $xml->Resposta->Erro);
        }

        $return = (object) array();
        $return->success = (bool) $xml->Resposta->Status == 'Sucesso';
        $return->id = (string) $xml->Resposta->ID;
        $return->token = (string) $xml->Resposta->Token;

        $moduleCredential = $this->getModuleCredential($this->environment);
        $return->payment_url = $moduleCredential['base'] . "Instrucao.do?token=" . $return->token;

        return $return;
    }

    /**
     * Method removeInstruction()
     *
     * remove token at Moip
     *
     * @return boolean
     * @access public
     */
    function removeInstruction($token) {

        $moduleCredential = $this->getModuleCredential($this->environment);
        $baseURL = $moduleCredential['base'] . "ws/alpha/RemoverInstrucaoPost";

        $auth = base64_encode($this->credential['token'] . ':' . $this->credential['key']);
        $header[] = "Authorization: Basic " . $auth;

        $options = array(CURLOPT_URL => $baseURL,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_USERAGENT => 'Mozilla/4.0',
            CURLOPT_POSTFIELDS => '<RemoverInstrucaoPost> <Token>' . $token . '</Token> </RemoverInstrucaoPost>',
            CURLOPT_RETURNTRANSFER => true
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $ret = curl_exec($ch);
        $err = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);


        if ($info['http_code'] == "200") {
            $res = simplexml_load_string($ret);
            if ($res->Resposta->Status == "Sucesso") {

                return 'Sucesso';
            } else {
                return 'Falha';
            }
        } else {
            return 'Falha';
        }
    }

    /**
     * Method getAnswer()
     *
     * Gets cep
     *
     * @return object
     * @access public
     */
    function getCEP($cep) {

        $cep = preg_replace("/[^0-9]/", "", $cep);
        $ch = curl_init();
        $timeout = 60;

        $options = array(CURLOPT_URL => "https://www.moip.com.br/ws/util/cep=$cep",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout
        );

        curl_setopt_array($ch, $options);
        $ret = curl_exec($ch);
        $err = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);


        if ($info['http_code'] == "200") {
            $res = simplexml_load_string($ret);
            if ($res->Resposta->Status == "Sucesso") {
                
                $address = array('Logradouro' => $res->Resposta->Logradouro,
                    'Bairro' => $res->Resposta->Bairro,
                    'Cidade' => $res->Resposta->Cidade,
                    'UF' => $this->getUF($res->Resposta->UF));

                return $address;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /* caso consulta de CEP nÃ£o esteja ativo sistema tenta identificar UF com base no estado digitado.
     *
     */

    function getUF($setUf) {

        $setUf = strtolower($setUf);
        $uf_estado = preg_replace("[^a-zA-Z0-9]", "", $setUf);

        if ($uf_estado == "acre")
            $UF = "AC";
        if ($uf_estado == "alagoas")
            $UF = "AL";
        if ($uf_estado == "amazonas")
            $UF = "AM";
        if (substr($uf_estado, 0, 4) == "amap")
            $UF = "AP";
        if ($uf_estado == "bahia")
            $UF = "BA";
        if (substr($uf_estado, 0, 4) == "cear")
            $UF = "CE";
        if ($uf_estado == "distritofederal")
            $UF = "DF";
        if (substr($uf_estado, 0, 3) == "esp")
            $UF = "ES";
        if (substr($uf_estado, 0, 3) == "goi")
            $UF = "GO";
        if (substr($uf_estado, 0, 6) == "maranh")
            $UF = "MA";
        if ($uf_estado == "minasgerais")
            $UF = "MG";
        if ($uf_estado == "matogrossodosul")
            $UF = "MS";
        if ($uf_estado == "matogrosso")
            $UF = "MT";
        if (substr($uf_estado, 0, 3) == "par")
            $UF = "PA";
        if (substr($uf_estado, 0, 4) == "para" && substr($uf_estado, -2, 2) == "ba")
            $UF = "PB";
        if ($uf_estado == "pernambuco")
            $UF = "PE";
        if (substr($uf_estado, 0, 4) == "piau")
            $UF = "PI";
        if (substr($uf_estado, 0, 5) == "paran")
            $UF = "PR";
        if ($uf_estado == "riodejaneiro")
            $UF = "RJ";
        if ($uf_estado == "riograndedonorte")
            $UF = "RN";
        if (substr($uf_estado, 0, 4) == "rond")
            $UF = "RO";
        if ($uf_estado == "roraima")
            $UF = "RR";
        if ($uf_estado == "riograndedosul")
            $UF = "RS";
        if ($uf_estado == "santacatarina")
            $UF = "SC";
        if ($uf_estado == "sergipe")
            $UF = "SE";
        if (substr($uf_estado, -5, 5) == "paulo")
            $UF = "SP";
        if ($uf_estado == "tocantins")
            $UF = "TO";
        if ($UF == "")
            $UF = $uf_estado;

        return $UF;
    }

    function getEnvironment() {
        return $this->environment;
    }

    function getXmlSent() {
        return $this->xmlSent;
    }

    function getModuleCredential($environment) {
        if($environment == "producao"){
            $moduleCredential = array('key' => 'Y8DIATTADUNVOSXKDN0JVDAQ1KU7UPJHEGPM7SBA',
                                      'token' => 'FEE5P78NA6RZAHBNH3GLMWZFWRE7IU3D',
                                      'base' => 'https://www.moip.com.br/');
        }else{
            $moduleCredential = array('key' => 'ABABABABABABABABABABABABABABABABABABABAB',
                                      'token' => '01010101010101010101010101010101',
                                      'base' => 'https://desenvolvedor.moip.com.br/sandbox/');
        }
        return $moduleCredential;
    }

    function getKeyToolsMoip($login){        
        return 1;
    }


}
?>
