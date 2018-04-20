<?php
namespace Tests\Unit;

use App\Game\Human;
use App\Game\Play;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PickCardTest extends TestCase
{
  protected $state = [];

  protected function setUp()
  {
    $this->createApplication();

    $user = User::find(3);
    Auth::login($user);

    $this->state = $state = json_decode(
      json_encode([
        "id" => "3c988174-a41f-4a51-a155-ba20c89db59c",
        "creator" => ["id" => 3, "name" => "root"],
        "deck" => [
          "content" => [
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 2,
              "card_name" => "clown",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 2,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 2]
            ],
            [
              "id" => 3,
              "card_name" => "knight",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 3,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 3]
            ],
            [
              "id" => 4,
              "card_name" => "priestess",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 4,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 4]
            ],
            [
              "id" => 5,
              "card_name" => "sorcerer",
              "choose_players" => 1,
              "choose_players_or_me" => 1,
              "choose_card_name" => 0,
              "value" => 5,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 5]
            ],
            [
              "id" => 6,
              "card_name" => "general",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 6,
              "number_copies" => 1,
              "pivot" => ["deck_id" => 1, "card_id" => 6]
            ],
            [
              "id" => 7,
              "card_name" => "minister",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 7,
              "number_copies" => 1,
              "pivot" => ["deck_id" => 1, "card_id" => 7]
            ],
            [
              "id" => 8,
              "card_name" => "princess_prince",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 8,
              "number_copies" => 1,
              "pivot" => ["deck_id" => 1, "card_id" => 8]
            ]
          ],
          "name" => [["deck_name" => "Original"]]
        ],
        "winning_rounds" => 7,
        "is_finished" => false,
        "players" => [
          [
            "id" => 3,
            "name" => "root",
            "hand" => [
              [
                "id" => 6,
                "card_name" => "general",
                "choose_players" => 1,
                "choose_players_or_me" => 0,
                "choose_card_name" => 0,
                "value" => 6,
                "number_copies" => 1,
                "pivot" => ["deck_id" => 1, "card_id" => 6]
              ]
            ],
            "turn" => 0,
            "winning_rounds_count" => 0,
            "immunity" => false,
            "can_play" => 0,
            "ia" => 0
          ],
          [
            "id" => 15242253515201,
            "name" => "IA",
            "hand" => [
              [
                "id" => 2,
                "card_name" => "clown",
                "choose_players" => 1,
                "choose_players_or_me" => 0,
                "choose_card_name" => 0,
                "value" => 2,
                "number_copies" => 2,
                "pivot" => ["deck_id" => 1, "card_id" => 2]
              ]
            ],
            "winning_rounds_count" => 0,
            "immunity" => false,
            "ia" => 1
          ]
        ],
        "current_player" => 0,
        "current_round" => [
          "number" => 1,
          "pile" => [
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 2,
              "card_name" => "clown",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 2,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 2]
            ],
            [
              "id" => 7,
              "card_name" => "minister",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 7,
              "number_copies" => 1,
              "pivot" => ["deck_id" => 1, "card_id" => 7]
            ],
            [
              "id" => 3,
              "card_name" => "knight",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 3,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 3]
            ],
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 4,
              "card_name" => "priestess",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 4,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 4]
            ],
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 1,
              "card_name" => "soldier",
              "choose_players" => 1,
              "choose_players_or_me" => 0,
              "choose_card_name" => 1,
              "value" => 1,
              "number_copies" => 5,
              "pivot" => ["deck_id" => 1, "card_id" => 1]
            ],
            [
              "id" => 8,
              "card_name" => "princess_prince",
              "choose_players" => 0,
              "choose_players_or_me" => 0,
              "choose_card_name" => 0,
              "value" => 8,
              "number_copies" => 1,
              "pivot" => ["deck_id" => 1, "card_id" => 8]
            ],
            [
              "id" => 5,
              "card_name" => "sorcerer",
              "choose_players" => 1,
              "choose_players_or_me" => 1,
              "choose_card_name" => 0,
              "value" => 5,
              "number_copies" => 2,
              "pivot" => ["deck_id" => 1, "card_id" => 5]
            ]
          ],
          "played_cards" => [
            [
              -1,
              [
                "id" => 3,
                "card_name" => "knight",
                "choose_players" => 1,
                "choose_players_or_me" => 0,
                "choose_card_name" => 0,
                "value" => 3,
                "number_copies" => 2,
                "pivot" => ["deck_id" => 1, "card_id" => 3]
              ]
            ],
            [
              -1,
              [
                "id" => 5,
                "card_name" => "sorcerer",
                "choose_players" => 1,
                "choose_players_or_me" => 1,
                "choose_card_name" => 0,
                "value" => 5,
                "number_copies" => 2,
                "pivot" => ["deck_id" => 1, "card_id" => 5]
              ]
            ],
            [
              -1,
              [
                "id" => 4,
                "card_name" => "priestess",
                "choose_players" => 0,
                "choose_players_or_me" => 0,
                "choose_card_name" => 0,
                "value" => 4,
                "number_copies" => 2,
                "pivot" => ["deck_id" => 1, "card_id" => 4]
              ]
            ]
          ],
          "current_players" => [0, 1]
        ],
        "test" => [],
        "started" => true
      ])
    );
  }

  public function testPickCard()
  {
    $nbCardsBefore = count($this->state->players[0]->hand);
    $state = Play::pickCard($this->state, 0, false);
    $nbCardsAfter = count($state->players[0]->hand);
    $this->assertGreaterThan($nbCardsBefore, $nbCardsAfter);
  }

  public function testPlayPickCard()
  {
    $nbCardsBefore = count($this->state->players[0]->hand);
    $state = Human::play($this->state, ['action' => 'pick_card']);
    $nbCardsAfter = count($state->players[0]->hand);
    $this->assertGreaterThan($nbCardsBefore, $nbCardsAfter);
  }

  public function testPlaySoldierSuccess() // root choose the right card name
  {
    $state = $this->state;

    $nbRounds = $state->current_round->number;

    // add the soldier card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
      "id" => 1,
      "card_name" => "soldier",
      "choose_players" => 1,
      "choose_players_or_me" => 0,
      "choose_card_name" => 1,
      "value" => 1,
      "number_copies" => 5,
      "pivot" => ["deck_id" => 1, "card_id" => 1]
    ];

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 1,
      'choosen_player' => 1,
      'choosen_card_name' => 'clown'
    ]);

    $nbRoundsAfter = $state->current_round->number;
    $this->assertGreaterThan($nbRounds, $nbRoundsAfter);
  }

  public function testPlaySoldierFail() // root choose the wrong card name
  {
    $state = $this->state;

    $nbRounds = $state->current_round->number;

    // add the soldier card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
      "id" => 1,
      "card_name" => "soldier",
      "choose_players" => 1,
      "choose_players_or_me" => 0,
      "choose_card_name" => 1,
      "value" => 1,
      "number_copies" => 5,
      "pivot" => ["deck_id" => 1, "card_id" => 1]
    ];

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 1,
      'choosen_player' => 1,
      'choosen_card_name' => 'minister'
    ]);

    $nbRoundsAfter = $state->current_round->number;
    $this->assertEquals($nbRounds, $nbRoundsAfter);
  }

  public function testPlayKnight()
  {
    $state = $this->state;

    $nbRounds = $state->current_round->number;

    // add the knight card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
      "id" => 3,
      "card_name" => "knight",
      "choose_players" => 1,
      "choose_players_or_me" => 0,
      "choose_card_name" => 0,
      "value" => 3,
      "number_copies" => 2,
      "pivot" => ["deck_id" => 1, "card_id" => 3]
    ];

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 3,
      'choosen_player' => 1
    ]);

    $nbRoundsAfter = $state->current_round->number;
    $this->assertGreaterThan($nbRounds, $nbRoundsAfter);
  }

  public function testPlayPriestess() 
  {
    $state = $this->state;

    $immunity = $state->players[0]->immunity;

    // add the priestess card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
   		"id" => 4,
		"card_name" => "priestess",
		"choose_players" => 0,
		"choose_players_or_me" => 0,
		"choose_card_name" => 0,
		"value" => 4,
		"number_copies" => 2,
		"pivot" => ["deck_id" => 1, "card_id" => 4]
    ];

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 4
    ]);

    $immunityAfter = $state->players[0]->immunity;
    $this->assertNotEquals($immunity, $immunityAfter);
  }

  public function testPlaySorcerer() 
  {
    $state = $this->state;

    $pile = count($state->current_round->pile);

    // add the sorcerer card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
   		"id" => 5,
		"card_name" => "sorcerer",
		"choose_players" => 1,
		"choose_players_or_me" => 1,
		"choose_card_name" => 0,
		"value" => 5,
		"number_copies" => 2,
		"pivot" => ["deck_id" => 1, "card_id" => 5]
    ];

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 5,
      'choosen_player' => 1
    ]);

    $pileAfter = count($state->current_round->pile);
    $this->assertGreaterThan($pile, $pileAfter);
  }

  public function testPlayGeneral()
  {
    $state = $this->state;

    // add the soldier card in his hand
    $state->players[0]->can_play = 1;
    $state->players[0]->hand[] = [
      "id" => 1,
      "card_name" => "soldier",
      "choose_players" => 1,
      "choose_players_or_me" => 0,
      "choose_card_name" => 1,
      "value" => 1,
      "number_copies" => 5,
      "pivot" => ["deck_id" => 1, "card_id" => 1]
    ];

    $cardRoot = $state->players[0]->hand[1]->name;
    $cardIa = $state->players[1]->hand[0]->name; 

    $state = Human::play($this->state, [
      'action' => 'play_card',
      'played_card' => 3,
      'choosen_player' => 1
    ]);

    $cardRootAfter = $state->players[0]->hand[0]->name;
    $cardIaAfter = $state->players[1]->hand[0]->name;
    $this->assertEquals($cardRoot, $cardIaAfter);
    $this->assertEquals($cardIa, $cardRootAfter);
  }
}
