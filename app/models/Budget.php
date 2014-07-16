<?php

/**
 * Budget
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
 * @method static \Illuminate\Database\Query\Builder|\Budget whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Budget whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Budget whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\Budget whereName($value) 
 * @method static \Illuminate\Database\Query\Builder|\Budget whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\Budget whereClass($value) 
 */
class Budget extends Component {
    protected $isSubclass = true;

    public static $factory = [
        'name' => 'string',
        'user_id' => 'factory|User',
        'class' => 'Budget'
    ];

} 