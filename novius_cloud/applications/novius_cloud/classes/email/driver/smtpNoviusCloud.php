<?php

class Email_Driver_Smtpnoviuscloud extends \Email\Email_Driver_Smtp
{
    protected function _send()
    {
        $message = $this->build_message(true);

        if(empty($this->config['smtp']['host']) or empty($this->config['smtp']['port']))
        {
            throw new \FuelException('Must supply a SMTP host and port, none given.');
        }

        // Use authentication?
        $authenticate = ! empty($this->config['smtp']['username']) and ! empty($this->config['smtp']['password']);

        // Connect
        $this->smtp_connect();

        // Authenticate when needed
        $authenticate and $this->smtp_authenticate();

        // MODIF NOVIUS CLOUD
        // On veut forcer un return-path différent du header From
        // http://redmine.lyon.novius.fr/issues/6324
        $return_path = ($this->config['return_path'] !== false) ? $this->config['return_path'] : $this->config['from']['email'];
        $this->smtp_send('MAIL FROM:<'.$return_path.'>', 250);

        foreach(array('to', 'cc', 'bcc') as $list)
        {
            foreach($this->{$list} as $recipient)
            {
                $this->smtp_send('RCPT TO:<'.$recipient['email'].'>', array(250, 251));
            }
        }

        // Prepare for data sending
        $this->smtp_send('DATA', 354);

        $lines = explode($this->config['newline'], $message['header'].$this->config['newline'].preg_replace('/^\./m', '..$1', $message['body']));

        foreach($lines as $line)
        {
            if(substr($line, 0, 1) === '.')
            {
                $line = '.'.$line;
            }
            fputs($this->smtp_connection, $line.$this->config['newline']);
        }

        // Finish the message
        $this->smtp_send('.', 250);

        // Close the connection
        $this->smtp_disconnect();

        return true;
    }
}
