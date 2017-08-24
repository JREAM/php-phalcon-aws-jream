<?php
declare(strict_types=1);

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Submit;
use Phalcon\Validation\Validator;

class NewsletterForm extends \Phalcon\Forms\Form
{

    public function initialize()
    {
        $email = new Text('email', [
            'placeholder' => 'Email',
            'class'       => 'form-control input-lg',
        ]);

        $email->addValidators([
            new Validator\PresenceOf([
                'message' => 'Your email is required.',
            ]),
            new Validator\Email([
                'message' => 'Your email is not valid.',
            ]),
        ]);

        $this->add($email);

        $this->add(new Submit('submit', [
            'value' => 'Subscribe',
            'class' => 'btn btn-lg btn-primary btn-block',
        ]));
    }

    // --------------------------------------------------------------
}
