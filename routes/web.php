<?php
Route::get('/', function () {
  return response()->json([
    "Please go to the front part at: https://love-letter.ludovic-muller.fr"
  ]);
});
