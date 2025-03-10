<?php

namespace App\Services;

use Illuminate\Http\Request;
use smpp\{ Address, SMPP, Client as SmppClient, transport\Socket};

class SMPPService
{
    const DEFAULT_SENDER = '01839308129';
    protected $smppClient;
    protected $transport;
    protected $host;
    protected $username;
    protected $password;
    protected $from;
    protected $to;

    private $client;

    public function __construct()
    {
        $host = env('SMPP_HOST');
        $port = env('SMPP_PORT', 2775);
        $username = env('SMPP_USERNAME', 'your-username');
        $password = env('SMPP_PASSWORD', 'your-password');
        // dd($host, $port, $username, $password);
        $timeout = env('SMPP_TIMEOUT', 99000);
        $debug = env('SMPP_DEBUG', true);
        $from = env('SMPP_FROM', self::DEFAULT_SENDER);

        try {
            $this->transport = new Socket([$host], $port);
            $this->transport->setRecvTimeout($timeout);
            $this->transport->setSendTimeout($timeout);
            $this->smppClient = new SmppClient($this->transport);
            // Activate binary hex-output of server interaction
            $this->smppClient->debug = $debug;

            $this->host = $host;
            $this->password = $password;
            $this->username = $username;

            $this->from = new Address($from, SMPP::TON_ALPHANUMERIC);
        } catch (\Exception $e) {
            throw new \Exception('SMPP Connection Error: ' . $e->getMessage());
        }
        
    }

    /**
     * @param $sender
     * @param $ton
     * @return $this
     * @throws \Exception
     */
    public function setSender($sender, $ton)
    {
        return $this->setAddress($sender, 'from', $ton);
    }

    /**
     * @param $address
     * @param $ton
     * @return $this
     * @throws \Exception
     */
    public function setRecipient($address, $ton)
    {
        return $this->setAddress($address, 'to', $ton);
    }

    /**
     * @param $address
     * @param string $type
     * @param int $ton
     * @param int $npi
     * @return $this
     * @throws \Exception
     */
    protected function setAddress($address, string $type, $ton = SMPP::TON_UNKNOWN, $npi = SMPP::NPI_UNKNOWN)
    {
        // some example of data preparation
        if($ton === SMPP::TON_INTERNATIONAL){
             $npi = SMPP::NPI_E164;
        }
        $this->$type = new Address($address, $ton, $npi);
        return $this;
    }

    /**
     * @param string $message
     */
    public function sendSMS($to, $message)
    {
        try {
            set_time_limit(-1);
            // Set the recipient
            $this->setRecipient($to, SMPP::TON_INTERNATIONAL);
            // Open transport connection
            if (!$this->transport->isOpen()) {

                $this->transport->open();
                // Add a small delay after opening the connection
                usleep(100000); // 100ms delay
            }

            // Bind as transceiver
            $this->smppClient->bindTransceiver($this->username, $this->password);

            // Send the message with UCS2 encoding for universal character support
            $this->smppClient->sendSMS($this->from, $this->to, $message, null, SMPP::DATA_CODING_UCS2);

            // Close the connection after sending
            $this->smppClient->close();
            $this->transport->close();

            return true;
        } catch (\Exception $e) {
            // Ensure connections are closed on error
            try {
                $this->smppClient->close();
                $this->transport->close();
            } catch (\Exception $closeError) {
                // Ignore close errors
            }
            throw new \Exception('SMS Sending Error: ' . $e->getMessage());
        }
    }
}

