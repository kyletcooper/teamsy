<?php
 
namespace WRD\Teamsy\Rules;
 
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use WRD\Teamsy\Models\Invitation;

class CanInvite implements ValidationRule
{
	/**
	 * The team to validate the invitation for.
	 * 
	 * @var Model|callable $team
	 */
	protected mixed $team;

	/**
	 * Create an invitation validation rule.
	 * 
	 * @param Model $team
	 */
	public function __construct( Model|callable $team )
	{
		$this->team = $team;
	}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
		$team = is_callable( $this->team ) ? call_user_func( $this->team ) : $this->team;

		// Check the email is valid.
		if( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ){
			$fail( "The :attribute must be a valid email address." );
		}

		// Check that the user doesn't already have an invitation.
		if( Invitation::existsForEmail( $team, $value ) ){
			$fail( "There is already an invitation pending for this email address." );
		}

		// Check that the user isn't already on the team.
		if( $team->hasMemberWithEmail( $value ) ){
			$fail( "There is already an user on the team with this email address." );
		}
    }
}

