<?php
namespace doctorzhang\mailerqueue;
use Yii;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        $redis = Yii::$app->redis;
        if(empty($redis)) {
            throw \yii\base\InvalidConfigException('redis not found in config.');
        }
        $mailer = Yii::$app->mailer;
        if(empty($mailer) || !$redis->select($mailer->db)) {
            throw \yii\base\InvalidConfigException('db not found in config.');
        }

        $message = [];
        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
        $message['cc'] = is_array($this->getCc()) ? array_keys($this->getCc()) : $this->getCc();
        $message['bcc'] = is_array($this->getBcc()) ? array_keys($this->getBcc()) : $this->getBcc();
        $message['reply_to'] = is_array($this->getReplyTo()) ? array_keys($this->getReplyTo()) : $this->getReplyTo();
        $message['charset'] = is_array($this->getCharset()) ? array_keys($this->getCharset()) : $this->getCharset();
        $message['subject'] = is_array($this->getSubject()) ? array_keys($this->getSubject()) : $this->getSubject();
        //邮件子信息
        $parts = $this->getSwiftMessage()->getChildren();
        if(!is_array($parts) || !sizeof($parts)) {
            $parts = $this->getSwiftMessage();
        }
        foreach($parts as $part) {
            if(!$part instanceof \Swift_Mime_Attachment) {
                switch ($part->getContentType()) {
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
                }
                if(!$message['charset']) {
                    $message['charset'] = $part->getCharset();
                }
            }
        }
        //写入redis队列并返回
        return $redis->rpush($mailer->key, json_encode($message));
    }
}