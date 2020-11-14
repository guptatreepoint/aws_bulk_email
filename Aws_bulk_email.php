<?php

/*
 * @class Aws_bulk_email
 * @author Sumit Kumar Gupta
 * @purpose send bulk email via amazon AWS SES
 */

 
require_once ('email_constant.php');
require_once ('third_party/Aws/aws-autoloader.php');

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class Aws_bulk_email {

    /**
     * function helps to send email in bulk with SES
     * @param  array $data 
     *         receipients,
     *         subject,
     *         message
     *  data must be multidimensional array like 
     *      [
     *          [
     *              'id' => '1',
     *              'receipients' => 'firstemail@email.com',
     *              'subject' => 'Subject 1 here',
     *              'message' => 'message 1 here'
     *          ],
     *          [
     *              'id' => '2',
     *              'receipients' => 'secondemail@email.com',
     *              'subject' => 'Subject2 here',
     *              'message' => 'message2 here'
     *          ]
     *      ]
     * @return array
     */
    public function sendEmail($data) {

        try {
            if(!is_array($data) && empty($data)){
                return;
            }

            $destinations = $sendingMessageData = array();
            $index = -1; //we can't take foreach key here

            foreach ($data as $key => $value) {

                $index = $index + 1;
               
                $replacementData = [
                    'subject' => $value['subject'],
                    'message' => $value['message']
                ];

                $destinations[] = [
                        'Destination'  => [
                            'ToAddresses'  => [$value['receipients']],
                        ],
                        'ReplacementTemplateData' => json_encode($replacementData)
                    ];

                $sendingMessageData[$index] = $value['id'];
            }
            
            $client = new SesClient([
                'credentials' => array(
                        'key' => ACCESS_KEY,
                        'secret'  => SECRET_KEY
                    ),
                'region'      => AWS_REGION,
                'version'     => AWS_VERSION
            ]);

            $sentResponse = $client->sendBulkTemplatedEmail([
                'DefaultTemplateData'  => "{\"subject\":\"unknown\", \"message\":\"unknown\"}",             
                'Destinations'  => $destinations,
                'Source'  => AWS_EMAIL_FROM,
                'Template'  => EMAIL_TEMPLATE_NAME,
            ]);

            return [
                'response' => $sentResponse,
                'send_mail_ids' => $sendingMessageData
            ];
        } catch (AwsException $ex) {
            return [
                'response' => $ex->getMessage()
            ];
        }
    }

    /**
     * function helps to check that email template is available or not
     * if not available then create a new one
     * @return boolean
     */
    public function checkBulkEmailTemplateExistsOnAws(){

        $client = new SesClient([
            'credentials' => array(
                    'key' => ACCESS_KEY,
                    'secret'  => SECRET_KEY
                ),
            'region'      => AWS_REGION,
            'version'     => AWS_VERSION,
        ]);

        //get list of 10 email templates
        $templateLists = $client->listTemplates([
            'MaxItems' => 10
        ]);
        
        if(!isset($templateLists['TemplatesMetadata'])){
            $this->createBulkEmailTemplate($client);
            return true;
        }

        $templateNameColumn = array_column($templateLists['TemplatesMetadata'], 'Name');
        $templateNameSearch = array_search(EMAIL_TEMPLATE_NAME, $templateNameColumn);

        if($templateNameSearch === false){
            $this->createBulkEmailTemplate($client);
            return true;
        }
        return true;
    }

    /**
     * function helps to create email template
     * @param  ses client object $client [in this new SesClient() function data]
     * @return boolean
     */
    private function createBulkEmailTemplate($client){
        
        try{
            $result = $client->createTemplate([
                 'Template' => [
                    'TemplateName' => EMAIL_TEMPLATE_NAME,
                    'SubjectPart'  => "{{subject}}",
                    'TextPart'      => "{{message}}",
                    'HtmlPart'     => "{{message}}"
                ],
            ]);
            return true;
        }catch(AwsException $e) {
            return false;
        }
    }
}
