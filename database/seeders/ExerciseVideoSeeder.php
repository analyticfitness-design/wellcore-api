<?php

namespace Database\Seeders;

use App\Models\ExerciseVideo;
use Illuminate\Database\Seeder;

class ExerciseVideoSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if empty
        if (ExerciseVideo::count() > 0) return;

        $videos = [
            // Chest exercises
            ['title' => 'Press de Banca', 'youtube_url' => 'https://youtu.be/rT7DgCr-3pg', 'youtube_id' => 'rT7DgCr-3pg', 'gender' => 'both', 'category' => 'chest', 'muscle_group' => 'chest'],
            ['title' => 'Press Inclinado', 'youtube_url' => 'https://youtu.be/Qqd9mQwvyL4', 'youtube_id' => 'Qqd9mQwvyL4', 'gender' => 'both', 'category' => 'chest', 'muscle_group' => 'chest'],
            ['title' => 'Aperturas con Mancuernas', 'youtube_url' => 'https://youtu.be/Iqq4uaWAI-A', 'youtube_id' => 'Iqq4uaWAI-A', 'gender' => 'both', 'category' => 'chest', 'muscle_group' => 'chest'],
            ['title' => 'Flexiones', 'youtube_url' => 'https://youtu.be/IODxDxX7oi4', 'youtube_id' => 'IODxDxX7oi4', 'gender' => 'both', 'category' => 'chest', 'muscle_group' => 'chest'],
            ['title' => 'Máquina de Pecho', 'youtube_url' => 'https://youtu.be/bR1ggk_iESE', 'youtube_id' => 'bR1ggk_iESE', 'gender' => 'both', 'category' => 'chest', 'muscle_group' => 'chest'],

            // Back exercises
            ['title' => 'Peso Muerto', 'youtube_url' => 'https://youtu.be/op9kVnSso6Q', 'youtube_id' => 'op9kVnSso6Q', 'gender' => 'both', 'category' => 'back', 'muscle_group' => 'back'],
            ['title' => 'Dominadas', 'youtube_url' => 'https://youtu.be/eGo4IYlbE5g', 'youtube_id' => 'eGo4IYlbE5g', 'gender' => 'both', 'category' => 'back', 'muscle_group' => 'back'],
            ['title' => 'Remo con Barra', 'youtube_url' => 'https://youtu.be/9efgcAjQe7E', 'youtube_id' => '9efgcAjQe7E', 'gender' => 'both', 'category' => 'back', 'muscle_group' => 'back'],
            ['title' => 'Remo Inclinado', 'youtube_url' => 'https://youtu.be/JEfArnYtjwE', 'youtube_id' => 'JEfArnYtjwE', 'gender' => 'both', 'category' => 'back', 'muscle_group' => 'back'],
            ['title' => 'Jalón Lateral', 'youtube_url' => 'https://youtu.be/wmFvwyH80s4', 'youtube_id' => 'wmFvwyH80s4', 'gender' => 'both', 'category' => 'back', 'muscle_group' => 'back'],

            // Legs exercises
            ['title' => 'Sentadilla', 'youtube_url' => 'https://youtu.be/ultWZbUMPL8', 'youtube_id' => 'ultWZbUMPL8', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],
            ['title' => 'Sentadilla Frontal', 'youtube_url' => 'https://youtu.be/2dF2-b9dLhw', 'youtube_id' => '2dF2-b9dLhw', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],
            ['title' => 'Prensa de Piernas', 'youtube_url' => 'https://youtu.be/IZxyjW7MIAI', 'youtube_id' => 'IZxyjW7MIAI', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],
            ['title' => 'Extensión de Piernas', 'youtube_url' => 'https://youtu.be/yd2dBBTbYes', 'youtube_id' => 'yd2dBBTbYes', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],
            ['title' => 'Flexión de Piernas', 'youtube_url' => 'https://youtu.be/qXGzEsM9Sg0', 'youtube_id' => 'qXGzEsM9Sg0', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],
            ['title' => 'Zancadas', 'youtube_url' => 'https://youtu.be/D7KaRcUTQeE', 'youtube_id' => 'D7KaRcUTQeE', 'gender' => 'both', 'category' => 'legs', 'muscle_group' => 'legs'],

            // Shoulders exercises
            ['title' => 'Press Militar', 'youtube_url' => 'https://youtu.be/2yjwXTZQDDI', 'youtube_id' => '2yjwXTZQDDI', 'gender' => 'both', 'category' => 'shoulders', 'muscle_group' => 'shoulders'],
            ['title' => 'Elevación Lateral', 'youtube_url' => 'https://youtu.be/q4uo2oQyVBw', 'youtube_id' => 'q4uo2oQyVBw', 'gender' => 'both', 'category' => 'shoulders', 'muscle_group' => 'shoulders'],
            ['title' => 'Elevación Frontal', 'youtube_url' => 'https://youtu.be/3VczynT57-c', 'youtube_id' => '3VczynT57-c', 'gender' => 'both', 'category' => 'shoulders', 'muscle_group' => 'shoulders'],
            ['title' => 'Elevación Trasera', 'youtube_url' => 'https://youtu.be/leB1IlUqPkM', 'youtube_id' => 'leB1IlUqPkM', 'gender' => 'both', 'category' => 'shoulders', 'muscle_group' => 'shoulders'],

            // Arms exercises
            ['title' => 'Curl de Bíceps', 'youtube_url' => 'https://youtu.be/ykJmrZ5v0Oo', 'youtube_id' => 'ykJmrZ5v0Oo', 'gender' => 'both', 'category' => 'arms', 'muscle_group' => 'biceps'],
            ['title' => 'Curl Inclinado', 'youtube_url' => 'https://youtu.be/sQNToGo2fVs', 'youtube_id' => 'sQNToGo2fVs', 'gender' => 'both', 'category' => 'arms', 'muscle_group' => 'biceps'],
            ['title' => 'Tríceps Polea', 'youtube_url' => 'https://youtu.be/2-LAMcpzODU', 'youtube_id' => '2-LAMcpzODU', 'gender' => 'both', 'category' => 'arms', 'muscle_group' => 'triceps'],
            ['title' => 'Tríceps en Paralelas', 'youtube_url' => 'https://youtu.be/0326qNfEDkU', 'youtube_id' => '0326qNfEDkU', 'gender' => 'both', 'category' => 'arms', 'muscle_group' => 'triceps'],
            ['title' => 'Extensión de Tríceps', 'youtube_url' => 'https://youtu.be/6kALZiMwKgM', 'youtube_id' => '6kALZiMwKgM', 'gender' => 'both', 'category' => 'arms', 'muscle_group' => 'triceps'],

            // Glutes exercises
            ['title' => 'Hip Thrust', 'youtube_url' => 'https://youtu.be/LM8XHLYJoYs', 'youtube_id' => 'LM8XHLYJoYs', 'gender' => 'both', 'category' => 'glutes', 'muscle_group' => 'glutes'],
            ['title' => 'Patada de Glúteo', 'youtube_url' => 'https://youtu.be/PNxMAjRZa8w', 'youtube_id' => 'PNxMAjRZa8w', 'gender' => 'both', 'category' => 'glutes', 'muscle_group' => 'glutes'],
            ['title' => 'Abducción en Máquina', 'youtube_url' => 'https://youtu.be/xhUJdCx-g5w', 'youtube_id' => 'xhUJdCx-g5w', 'gender' => 'both', 'category' => 'glutes', 'muscle_group' => 'glutes'],

            // Abs exercises
            ['title' => 'Abdominales Crunches', 'youtube_url' => 'https://youtu.be/I1VY-vn0Ih8', 'youtube_id' => 'I1VY-vn0Ih8', 'gender' => 'both', 'category' => 'abs', 'muscle_group' => 'abs'],
            ['title' => 'Plancha', 'youtube_url' => 'https://youtu.be/ASdvN_XEl_c', 'youtube_id' => 'ASdvN_XEl_c', 'gender' => 'both', 'category' => 'abs', 'muscle_group' => 'abs'],
            ['title' => 'Elevación de Rodillas', 'youtube_url' => 'https://youtu.be/zTwPECdJVNQ', 'youtube_id' => 'zTwPECdJVNQ', 'gender' => 'both', 'category' => 'abs', 'muscle_group' => 'abs'],
        ];

        foreach ($videos as $video) {
            ExerciseVideo::create($video);
        }
    }
}
