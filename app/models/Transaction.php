<?php
use Phalcon\Mvc\Model\Behavior\SoftDelete;

class Transaction extends BaseModel
{

    /** @const SOURCE the table name */
    const SOURCE = 'transaction';

    /** @var array Saves on Memcached Queries */
    public static $_cache;

    public function initialize()
    {
        $this->addBehavior(new SoftDelete([
            'field' => 'is_deleted',
            'value' => 1
        ]));

        $this->setSource(self::SOURCE);
        $this->belongsTo("user_id", "User", "id");
        $this->hasOne("product_id", "Product", "id");
    }

    // --------------------------------------------------------------

    /**
     * This fixes an odd bug.
     * @return string Class Name in lowercase
     */
    public function getSource()
    {
        return self::SOURCE;
    }

    // --------------------------------------------------------------
    //
}

// End of File
// --------------------------------------------------------------