<?php
namespace doctorzhang\mailerqueue;
use Yii;

class MailQueue extends \yii\swiftmailer\Mailer
{
    public $messageClass = 'doctorzhang\mailerqueue\Message';
    public $db = '1';
    public $key = 'mails';

    public function process()
    {
        $redis = Yii::$app->redis;
        if(empty($redis) || !$redis->select($this->db)) {
            throw \yii\base\InvalidConfigException('db not config');
        }

        //从队列中获取所有待处理数据
        $messages = $redis->lrange($this->key, 0, -1);
        if($messages) {
            $messageObj = new Message;
            foreach($messages as $message) {
                $message = json_decode($message, true);
                if(empty($message) || !$this->setMessage($message, $messageObj)) {
                    throw new \ServerErrorHttpException('message error');
                }
                if($messageObj->send()) {
                    $redis->lrem($this->key, -1, json_encode($message));
                }
            }
        }
        return true;
    }

    public function setMessage($message, $messageObj)
    {
        if(empty($messageObj)) {
            return false;
        }

        if(!empty($message['from']) && !empty($message['to'])) {
            $messageObj->setFrom($message['from'])
                        ->setTo($message['to']);
            if(!empty($message['cc'])) {
                $messageObj->setCc($message['cc']);
            }
            if(!empty($message['bcc'])) {
                $messageObj->setBcc($message['bcc']);
            }
            if(!empty($message['reply_to'])) {
                $messageObj->setReplyTo($message['reply_to']);
            }
            if(!empty($message['charset'])) {
                $messageObj->setCharset($message['charset']);
            }
            if(!empty($message['subject'])) {
                $messageObj->setSubject($message['subject']);
            }
            if(!empty($message['html_body'])) {
                $messageObj->setHtmlBody($message['html_body']);
            }
            if(!empty($message['text_body'])) {
                $messageObj->setTextBody($message['text_body']);
            }
            return $messageObj;
        }
        return false;
    }
}