<?php
namespace LwMailer\Controller;

/**
 * Mailer Class to use only one function call but via config different
 * types of email sending or write the email into a logfile for testing.
 * 
 * @author Michael Mandt <michael.mandt@logic-works.de>
 * @package LwMailer
 */
class LwMailer
{

    protected $config;
    protected $mailConfig;

    public function __construct($mailConfig, $config)
    {
        $this->mailConfig = $mailConfig;
        $this->config = $config;
    }
    
    public function setMailTypeDebug()
    {
        $this->mailConfig["mailType"] = "debug";
    }

    public function setMailConfigEntryByKey($key, $value)
    {
        $this->mailConfig[$key] = $key;
    }


    /**
     * Sending Type by $sendMailType param.
     * 
     * @param array $mailInformationArray
     * @return bool
     */
    public function sendMail($mailInformationArray)
    {
        if($this->validateToMail($mailInformationArray["toMail"])) {        
            switch ($this->mailConfig["mailType"]) {
                case "zendMail":
                    return $this->zendMail($mailInformationArray);
                    break;

                case "phpMail":
                    return $this->phpMail($mailInformationArray);
                    break;

                case "debug":
                default:
                    return $this->debugMail($mailInformationArray);
                    break;
            }
        }
        return false;
    }

    /**
     * Email will be send via zendMail framework.
     * 
     * @param array $mailInformationArray
     * @return boolean
     * @throws Exception
     */
    private function zendMail($mailInformationArray)
    {
        require_once($this->config['path']['framework'] . "Zend/Mail.php");
        require_once($this->config['path']['framework'] . "Zend/Mail/Transport/Smtp.php");
        $mconfig = array();
        $mconfig["auth"]        = "login";
        $mconfig["username"]    = $this->mailConfig["username"];
        $mconfig["password"]    = $this->mailConfig["password"];
        $mconfig["port"]        = $this->mailConfig["port"];
        if (!empty($this->mailConfig['ssl'])) {
            $mconfig["ssl"]     = $this->mailConfig["ssl"];
        }
        $server                 = $this->mailConfig["server"];
        
        $subject = $this->mailConfig["subjectPrefix"] . $mailInformationArray["subject"];
        
        $transport = new \Zend_Mail_Transport_Smtp($server, $mconfig);
        $mail = new \Zend_Mail();
        
        try {
            $mail->setBodyText($mailInformationArray["message"].$this->buildSignature(), null, \Zend_Mime::ENCODING_BASE64);
            $mail->setFrom($this->mailConfig["from"], $this->mailConfig["from"]);
            $mail->setReplyTo($this->mailConfig["replyTo"], $name = null);
            $mail->addTo($mailInformationArray["toMail"], $mailInformationArray["toMail"]);
            $mail->setSubject($subject);
            $mail->send($transport);
            return true;
        } catch (\Zend_Mail_Exception $e) {
            die($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        
    }

    /**
     * Email will be send via php mail function.
     * 
     * @param array $mailInformationArray
     * @return boolean
     */
    private function phpMail($mailInformationArray)
    {
        $header =   'From: ' . $this->mailConfig["from"] . "\r\n" .
                    'Reply-To: ' . $this->mailConfig["replyTo"] . "\r\n" .
                    'Content-Type: text/plain;charset=iso-8859-1' . "\r\n" .
                    //'Content-Transfer-Encoding:  base64'. "\r\n" .
                    'Content-Transfer-Encoding: 8bit' . "\r\n" .
                    'X-Mailer: PHP-Mail/' . phpversion();

            mail(
                    $mailInformationArray["toMail"], 
                    $this->mailConfig["subjectPrefix"] . $mailInformationArray["subject"], 
                    $mailInformationArray["message"].$this->buildSignature(), 
                    $header
                );
            return true;
    }

    /**
     * Email will be saved into an logfile for testing.
     * 
     * @param array $mailInformationArray
     * @return boolean
     */
    private function debugMail($mailInformationArray)
    {
        if ($this->existsLogDir()) {
            $logfile = fopen($this->config['path']['web_resource'] . 'lw_logs/' . $this->mailConfig["mailDebugLogDir"] . $this->mailConfig["mailDebugLogFile"], "a");
            fwrite($logfile, "#######################################################################" . PHP_EOL);
            fwrite($logfile, date("d.m.Y - H:i:s") . PHP_EOL);
            fwrite($logfile, "From: " . $this->mailConfig["from"] . "  Reply-To:" . $this->mailConfig["replyTo"] . PHP_EOL . PHP_EOL);
            fwrite($logfile, "To: " . $mailInformationArray["toMail"] . PHP_EOL . PHP_EOL);
            fwrite($logfile, "Subject: " . $this->mailConfig["subjectPrefix"] . $mailInformationArray["subject"] . PHP_EOL . PHP_EOL . PHP_EOL);
            fwrite($logfile, $mailInformationArray["message"].$this->buildSignature() . PHP_EOL);
            fwrite($logfile, "#######################################################################" . PHP_EOL);
            fclose($logfile);
        }
        return true;
    }
    
    /**
     * A signature will be build from the mailconfig contact array.
     * 
     * @return string
     */
    private function buildSignature()
    {
        if($this->mailConfig["addSignature"]){
            $view = new \LwMailer\Views\SignatureView();
            return $view->render($this->mailConfig["contact"]);
        }
        
        return "";
    }
    
    /**
     * Logdirectory will be checked if it is existing and created
     * if the log directory is not existing.
     * 
     * @return boolean
     */
    private function existsLogDir()
    {
        $configLogDir = $this->config['path']['web_resource'].'lw_logs/'.str_replace("/", "", $this->mailConfig["mailDebugLogDir"]);

        if (!is_dir($configLogDir)) {
            mkdir($configLogDir);
            chmod($configLogDir,0775);
        }

        return true;
    }
    
    /**
     * Email validation.s
     * 
     * @param string $toMail
     * @return boolean
     */
    private function validateToMail($toMail)
    {
        if(filter_var($toMail, FILTER_VALIDATE_EMAIL) == false) {
            throw new \Exception("the toMail address is not valid");
            return false;
        }
        return true;
    }
}

/* ######## Definition der benoetigten Array Config und Parameter
   ["mailConfig"]
    mailType                    = "debug"
    mailDebugLogDir             = "mailLogs/"
    mailDebugLogFile            = "log.txt"
    from                        = ""
    replyTo                     = ""
    subjectPrefix               = ""    
    username                    = ""
    password                    = ""
    server                      = ""
    port                        = ""
    ssl                         = ""
    addSignature                = ""
    contact[organisationName]   = ""
    contact[name]               = ""
    contact[phone]              = ""
    contact[fax]                = ""
 */

 /*
   $mailInformationArray = array(
       "toMail"    = "",
       "subject"   = "",
       "message"   = ""
   );
  */
