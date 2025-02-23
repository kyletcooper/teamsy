<?php
 
namespace WRD\Teamsy\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;
use WRD\Teamsy\Models\TeamInspector;

class AbandonedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teamsy:abandoned {model?} {--all : Show the membership count of all teams, not just those with no members}';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List the teams with no members.';
 
    /**
     * Execute the console command.
     */
    public function handle( TeamInspector $inspector ): void
    {
        $models = $this->argument( 'model' ) ?
            [ $this->guessModel() ]
            : $inspector->getAllTeamModels();

        $this->line( '<fg=gray>Finding abandoned teams...</>' );
        $this->line( PHP_EOL );

        $populated = 0;
        $total = 0;

        foreach( $models as $model ){
            if( ! $inspector->isValidTeam( $model ) ){
                $this->error( "$model is not a valid team." );
                return;
            }

            $teams = $inspector->getTeamCounts( $model );
            $populated += collect( $teams )->map( fn( $team ) => $team->members_count )->sum();
            $total += count( $teams );
            
            $this->outputTable( $model, $teams );
        }

        $abandoned = $total - $populated;
        $name = Str::of( 'team' )->plural( $abandoned );
        $this->line( "<fg=gray>Found </><fg=bright-white>$abandoned</><fg=gray> abandoned $name out of </><fg=bright-white>$total</><fg=gray> total.</>" );
    }

    private function guessModel(): string {
        $model = $this->argument( 'model' );

        if( Str::startsWith( $model, '\\') ){
            dd( $model );
            return $model; // User has likely qualified this model with a namespace.
        }

        return "\\App\\Models\\$model";
    }

    private function outputTable( string $model, array $teams ){
        $table = (new Table( $this->output ))
            ->setStyle( 'box' )
            ->setHeaders([
                'ID',
                'Members',
                'Invitations',
                'Status',
                'Action'
            ]);

        $title = Str::of( $model )->classBasename()->headline()->plural();
        $table->setHeaderTitle( $title );

        $onlyAbandoned = ! $this->option( 'all' );

        $abandoned = 0;
        $total = 0;

        foreach( $teams as $team ){
            $isAbandoned = $team->members_count === 0;

            $total++;
            $abandoned += $isAbandoned ? 1 : 0;

            if( $onlyAbandoned && ! $isAbandoned ){
                continue;
            }

            $keyLine = "<fg=gray>#</>" . $team->getKey();

            $membersLine = Str::of( "member" )
                ->plural( $team->members_count )
                ->wrap( "<fg=gray>", "</>" )
                ->prepend( $this->highlightCount( $team->members_count ), " " );

            $invitesLine = Str::of( "invite" )
                ->plural( $team->invitations_count )
                ->wrap( "<fg=gray>", "</>" )
                ->prepend( $this->highlightCount( $team->invitations_count ), " " );

            $status = $isAbandoned ? "Abandoned" : "Populated";
            $statusLine = $this->highlightStatus( $status );

            $action = $isAbandoned ? "Skipped" : "Ignored";
            $actionLine = $this->highlightAction( $action );

            $table->addRow([
                $keyLine,
                $membersLine,
                $invitesLine,
                $statusLine,
                $actionLine
            ]);
        }

        
        $table->render();

        $name = Str::of( $model )->classBasename()->headline()->lower()->plural( $abandoned );
        $this->line( "<fg=gray>Found </><fg=bright-white>$abandoned</><fg=gray> abandoned $name out of </><fg=bright-white>$total</><fg=gray> total.</>" );
        $this->line( PHP_EOL );
    }

    private function highlightCount( int $count ): string{
        $colors = [
            0 => "red",
            1 => "yellow",
            2 => "yellow"
        ];

        $color = $colors[$count] ?? "white";

        return sprintf("<fg=%s>%s</>", $color, $count);
    }

    private function highlightStatus( string $status ): string{
        $colors = [
            'Abandoned' => "red",
            'Populated' => "gray",
        ];

        if( ! array_key_exists( $status, $colors ) ){
            $status .= ' (?)';
        }

        $color = $colors[$status] ?? "magenta";

        return sprintf("<fg=%s>%s</>", $color, $status);
    }

    private function highlightAction( string $action ): string{
        $colors = [
            'Ignored' => "gray",
            'Skipped' => "bright-white",
            'Deleted' => "red"
        ];

        if( ! array_key_exists( $action, $colors ) ){
            $action .= ' (?)';
        }

        $color = $colors[$action] ?? "magenta";

        return sprintf("<fg=%s>%s</>", $color, $action);
    }
}