<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Horoscopes;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Throwable;

class HoroscopesController extends Controller
{
    public function getUser()
    {
        return
            [
                'nome' => auth()->user()->name,
                'aniversario' => auth()->user()->birthday,
            ];
    }

    public function getSignoByDate($signo)
    {
        if ($signo == 'meusigno') {
            $date = auth()->user()->birthday;
            $signo = Horoscopes::where('start_date', '<=', $date)->where('end_date', '>=', $date)->first('signo');
            if (!$signo) return 'Capricórnio';
            return $signo['signo'];
        } else {
            return $signo;
        }
    }

    public function getHoroscopes($signo)
    {
        try {
            $signo = $this->getSignoByDate($signo);
            return Horoscopes::where(function ($query) use ($signo) {
                $query->Where('signo', 'like', $signo . '%');
                if ($query->count() == 0)
                    $query->orWhere(DB::raw('signo'), '=', DB::raw($signo));
            })->first();
        } catch (Throwable $th) {
            return null;
        }
    }

    public function getAllSigno()
    {
        $horoscopos = [];
        foreach (Horoscopes::all() as $horoscopo) {
            $horoscopos[] = [
                'signo' => $horoscopo->signo,
                'periodo' => $horoscopo->start_date . ' até ' . $horoscopo->end_date,
            ];
        }
        return response()->json($horoscopos);
    }

    public function basic($signo)
    {
        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo ' . $signo . ' não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'mensagem' => $horoscopo->message_basic,
            'trabalho' => $horoscopo->work_basic,
        ]);
    }

    public function premium($signo)
    {
        $faker = Faker::create();
        $numero = $faker->numberBetween(100000, 999999);

        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'mensagem' => $horoscopo->message_premium,
            'trabalho' => $horoscopo->work_basic,
            'sorte' => $horoscopo->lucky_premium,
            'amor' => $horoscopo->love_premium,
            'saude' => $horoscopo->health_premium,
            'mega-sena' => $numero,
        ]);
    }

    public function message($signo)
    {
        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'mensagem' => $horoscopo->message_premium,
        ]);
    }

    public function lucky($signo)
    {
        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'sorte' => $horoscopo->lucky_premium,
        ]);
    }

    public function love($signo)
    {
        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'amor' => $horoscopo->love_premium,
        ]);
    }

    public function health($signo)
    {
        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'saúde' => $horoscopo->health_premium,
        ]);
    }

    public function mega($signo)
    {
        $faker = Faker::create();
        $numero = $faker->numberBetween(100000, 999999);

        $signo = $this->getSignoByDate($signo);
        $horoscopo = $this->getHoroscopes($signo);
        if (!$horoscopo) return response()->json(['erro' => 'Signo não encontrado'], 404);
        return response()->json([
            'dados-usuario' => $this->getUser(),
            'signo' => $horoscopo->signo,
            'mega-sena' => $numero,
        ]);
    }
}
