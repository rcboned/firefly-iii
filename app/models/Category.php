<?php

/**
 * Category
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $name
 * @property integer $user_id
 * @property string $class
 * @property-read \Illuminate\Database\Eloquent\Collection|\Transaction[] $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @property-read \User $user
 * @method static \Illuminate\Database\Query\Builder|\Category whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Category whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Category whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Category whereName($value) 
 * @method static \Illuminate\Database\Query\Builder|\Category whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Category whereClass($value) 
 */
class Category extends Component
{
    protected $isSubclass = true;
    public static $factory = [
        'name' => 'string',
        'user_id' => 'factory|User',
        'class' => 'Category'
    ];
} 