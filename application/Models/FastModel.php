<?php
namespace App\Models;

use System\Core\BaseModel;

class FastModel extends BaseModel
{
    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = $table;
    }
}
