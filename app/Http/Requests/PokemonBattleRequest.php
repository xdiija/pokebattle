<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PokemonBattleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pokemon_one' => ['required', 'string', 'min:3'],
            'pokemon_two' => ['required', 'string', 'min:3', 'different:pokemon_one'],
        ];
    }

    public function messages(): array
    {
        return [
            'pokemon_one.required' => 'Informe o primeiro Pokemon.',
            'pokemon_one.string' => 'O primeiro Pokemon deve ser um texto.',
            'pokemon_one.min' => 'O primeiro Pokemon deve ter pelo menos :min caracteres.',
            'pokemon_two.required' => 'Informe o segundo Pokemon.',
            'pokemon_two.string' => 'O segundo Pokemon deve ser um texto.',
            'pokemon_two.min' => 'O segundo Pokemon deve ter pelo menos :min caracteres.',
            'pokemon_two.different' => 'Os Pokemons da batalha devem ser diferentes.',
        ];
    }
}
