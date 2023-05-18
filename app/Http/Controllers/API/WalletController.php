<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CoinCrypto;
use App\Models\HistoryTransition;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getWallets(Request $request)
    {
        $walletsData = [];
        if ($request->path() == 'api/criptomoeda/carteiras')
            $wallets = Wallet::all()->sortBy('coinCrypto.type');
        else
            $wallets = Wallet::where('user_id', auth()->user()->id)->get()->sortBy('coinCrypto.type');
        foreach ($wallets as $wallet) {
            $walletsData[] = [
                'id' => $wallet->id,
                'moeda' => $wallet->coinCrypto->type,
                'saldo' => $wallet->balance . ' ' . $wallet->coinCrypto->acronym,
                'saldo em real' => 'R$ ' . number_format($wallet->balance * $wallet->coinCrypto->value_in_real, 2, ',', '.'),
                'valor unitario' => 'R$ ' . number_format($wallet->coinCrypto->value_in_real, 2, ',', '.'),
                'Dono' => $wallet->user->name ?? 'Sem dono',
            ];
        }
        return response()->json([
            'carteiras' =>
                $walletsData
        ], 200);
    }

    private function getWalletInfo($id)
    {
        $wallet = Wallet::find($id);
        return [
            'id' => $wallet->id,
            'moeda' => $wallet->coinCrypto->type,
            'saldo' => $wallet->balance . ' ' . $wallet->coinCrypto->acronym,
            'saldo em real' => 'R$ ' . number_format($wallet->balance * $wallet->coinCrypto->value_in_real, 2, ',', '.'),
            'valor unitario' => 'R$ ' . number_format($wallet->coinCrypto->value_in_real, 2, ',', '.'),
            'Dono' => $wallet->user->name ?? 'Sem dono',
        ];
    }

    public function newWallet(Request $request)
    {
        $type_coin = strtolower($request->tipo);
        switch ($type_coin) {
            case 'btc':
            case 'bitcoin':
                $coin_crypto_id = 1;
                break;
            case 'eth':
            case 'ethereum':
                $coin_crypto_id = 2;
                break;
            case 'ltc':
            case 'litecoin':
                $coin_crypto_id = 3;
                break;
            default:
                return response()->json(['message' => 'Moeda não encontrada'], 404);
        }

        $existWallet = Wallet::where('user_id', auth()->user()->id)
            ->where('coin_crypto_id', $coin_crypto_id)->count();

        if ($existWallet > 0) {
            return response()->json(['message' => 'Carteira já existe'], 404);
        }

        $wallet = Wallet::create([
            'user_id' => auth()->user()->id,
            'balance' => $request->saldo ?? 0,
            'coin_crypto_id' => $coin_crypto_id,
        ]);

        return response()->json([
            'message' => 'Carteira criada com sucesso',
            'carteira' => $this->getWalletInfo($wallet->id),
        ], 201);
    }

    public function addBalance(Request $request)
    {
        $wallet = Wallet::where('user_id', auth()->user()->id)
            ->where('id', $request->id_carteira)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Carteira não encontrada'], 404);
        }

        $wallet->balance += $request->saldo;
        $wallet->save();

        $this->createHistoryTransition($request->saldo, 'Adicionado saldo na carteira', $wallet->id, $wallet->id);

        return response()->json([
            'message' => 'Saldo adicionado com sucesso',
            'carteira' => $this->getWalletInfo($request->id_carteira),
        ]);
    }

    public function removeBalance(Request $request)
    {
        $wallet = Wallet::where('user_id', auth()->user()->id)
            ->where('id', $request->id_carteira)->first();

        if (!$wallet) {
            return response()->json(['message' => 'Carteira não encontrada'], 404);
        }
        if ($wallet->balance < $request->saldo) {
            return response()->json(['message' => 'Saldo insuficiente'], 404);
        }

        $wallet->balance -= $request->saldo;
        $wallet->save();

        $this->createHistoryTransition($request->saldo, 'Removendo saldo na carteira', $wallet->id, $wallet->id);

        return response()->json([
            'message' => 'Saldo removido com sucesso',
            'carteira' => $this->getWalletInfo($request->id_carteira),
        ]);
    }

    public function balanceTransfer(Request $request)
    {
        $walletFrom = Wallet::where('user_id', auth()->user()->id)
            ->where('id', $request->id_carteira_origem)->first();
        $walletTo = Wallet::find(request()->id_carteira_destino);

        if (!$walletFrom) {
            return response()->json(['message' => 'Carteira de Origem não encontrada'], 404);
        }
        if (!$walletTo) {
            return response()->json(['message' => 'Carteira de Destino não encontrada'], 404);
        }

        if ($walletFrom->balance < $request->saldo) {
            return response()->json(['message' => 'Saldo insuficiente'], 404);
        }

        $saldo = $request->saldo;
        $walletFrom->balance -= $saldo;

        if ($walletFrom->coin_crypto_id != $walletTo->coin_crypto_id) {
            $saldo = $this->balanceConverter($saldo, $walletFrom->coin_crypto_id, $walletTo->coin_crypto_id);
        }

        $walletTo->balance += $saldo;
        $walletFrom->save();
        $walletTo->save();

        $this->createHistoryTransition($request->saldo, 'Realizando transferencia de saldo', $walletFrom->id, $walletTo->id);

        return response()->json([
            'message' => 'Saldo de ' . $saldo . ' transferido com sucesso',
            'carteira' => $this->getWalletInfo($request->id_carteira_origem),
        ]);
    }

    public function deleteWallet($walletId)
    {
        $wallet = Wallet::where('user_id', auth()->user()->id)
            ->where('id', $walletId)->first();
        if (!$wallet) {
            return response()->json(['message' => 'Carteira não encontrada'], 404);
        }
        $wallet->delete();
        return response()->json([
            'message' => 'Carteira deletada com sucesso',
        ], 200);
    }

    public function getHistoryTransitions()
    {
        $transitions = HistoryTransition::where('description', 'Realizando transferencia de saldo')
            ->orderBy('date', 'desc')->get();

        if ($transitions->count() == 0) {
            return response()->json([
                'message' => 'Nenhuma transação encontrada',
            ], 404);
        }

        $transitionData = [];

        foreach ($transitions as $transition) {
            $coin_type_balance_in_real = $transition->wallet->coinCrypto->value_in_real;
            $transitionData[] = [
                'transacao_id' => $transition->id,
                'data' => $transition->date,
                'valor' => $transition->amount . ' ' . $transition->wallet->coinCrypto->acronym,
                'valor_convertido' => 'R$' . number_format($transition->amount * $coin_type_balance_in_real, 2, ',', '.'),
                'carteira_origem' => $transition->from_wallet_id,
                'carteira_destino' => $transition->to_wallet_id,
            ];
        }
        return response()->json([
            'message' => 'Histórico de transações',
            'blockchain' => $transitionData,
        ], 200);
    }

    public function getHistoryTransitionsUser()
    {
        $wallets = Wallet::where('user_id', auth()->user()->id)->get();
        $walletData = [];
        $historyData = [];

        foreach ($wallets as $wallet) {
            foreach ($wallet->historyTransitions as $history) {
                $historyData[] = [
                    'transacao_id' => $history->id,
                    'data' => $history->date,
                    'descricao' => $history->description,
                    'valor' => $history->amount . ' ' . $history->wallet->coinCrypto->acronym,
                    'valor_convertido' => 'R$' . number_format($history->amount * $history->wallet->coinCrypto->value_in_real, 2, ',', '.'),
                    'carteira_origem' => $history->from_wallet_id,
                    'carteira_destino' => $history->to_wallet_id,
                ];
            }
            $walletData[] =
                [
                    'Carteira' => $wallet->id,
                    'History' => $historyData
                ];
        }

        return response()->json($walletData);
    }

    private function balanceConverter($saldo, $coin_from_id, $coin_to_id)
    {
        $coin_from = CoinCrypto::find($coin_from_id);
        $coin_to = CoinCrypto::find($coin_to_id);
        return $saldo * $coin_from->value_in_real / $coin_to->value_in_real;
    }

    private function createHistoryTransition($balance, $description, $walletFromId, $walletToId)
    {
        $transactions = HistoryTransition::create([
            'date' => date('Y-m-d H:i:s'),
            'amount' => $balance,
            'description' => $description,
            'from_wallet_id' => $walletFromId,
            'to_wallet_id' => $walletToId,
        ]);
    }
}
