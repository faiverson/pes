<?php

use Illuminate\Database\Seeder;
use Revolution\Google\Sheets\Facades\Sheets;
use PulkitJalan\Google\Facades\Google;

class FetchSpreadSheet extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('games')->truncate();
        $client = Google::getClient();
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $sheets = new \Google_Service_Sheets($client);
        $spreadsheetId = '1UkLLs2C_OstNisLFB0PhhnsHu9WtnEJC7dJa2SctKlc';
        $range = 'martes!B2:F77';
        $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();
        $this->createGames($rows);

        $range = 'martes!B82:F845';
        $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();
        $this->createGames($rows);
    }

    protected function createGames($rows)
    {
        $date = \Carbon\Carbon::now();
        foreach ($rows as $row) {
            $date = empty($row[0]) ? $date : \Carbon\Carbon::createFromFormat('Y-m-d', $row[0]);
            $players = explode('/', $row[1]);
            sort($players);
            $home = \App\Models\Team::where('name', "{$players[0]}-{$players[1]}")->first();
            $players = explode('/', $row[4]);
            sort($players);
            $away = \App\Models\Team::where('name', "{$players[0]}-{$players[1]}")->first();
            if(empty($home) || empty($away)) {
                $this->command->error("ERROR", $row);
            }
            try {
                $game = \App\Models\Game::create([
                    'team_home_id' => $home->id,
                    'team_away_id' => $away->id,
                    'team_home_score' => $row[2],
                    'team_away_score'=> $row[3],
                    'result'=> $row[2] >= $row[3] ? ($row[2] > $row[3] ? 'home' : 'draw')  : 'away',
                    'created_at'=> $date->format('Y-m-d H:i:s'),
                    'updated_at'=> $date->format('Y-m-d H:i:s'),
                ]);
            }
            catch (\Exception $e) {
                dd($row);
            }
            $this->command->info("Game created ID: {$game->id}");
        }
    }
}
