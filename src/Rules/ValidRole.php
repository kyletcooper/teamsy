<?php
 
namespace WRD\Teamsy\Rules;
 
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use WRD\Teamsy\Support\Facades\Roles;

class ValidRole implements ValidationRule
{
	/**
	 * The team type to find roles for.
	 * 
	 * @var ?string $teamType
	 */
	protected ?string $teamType = null;

	/**
	 * Create a role validation rule.
	 * 
	 * Optionally, roles can be scoped to a team.
	 * 
	 * @param ?string $teamType
	 */
	public function __construct( string $teamType = null )
	{
		$this->teamType = $teamType;
	}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ( ! Roles::validate( $value, $this->teamType ) ) {
            $fail('The :attribute must be valid role in this team.');
        }
    }
}

