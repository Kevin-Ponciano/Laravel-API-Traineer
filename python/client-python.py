import requests as requests
import json
import re


def json_indentado(obj):
    print(json.dumps(obj.json(), indent=4, ensure_ascii=False))


api_key = '17|dm3zwpTxHvIKgOxjPg9ssM6emSOCRYJdHR034NcO'
headers = {
    'Authorization': f'Bearer {api_key}',
    'Accept': 'application/json',
}

# Servidor local
url_horoscopo = 'http://127.0.0.1:8000/api/horoscopos'

uri_signos = url_horoscopo + '/signos'
uri_basico = url_horoscopo + '/basico/'
uri_premium = url_horoscopo + '/premium/'
uri_premium_mensagem = url_horoscopo + '/premium/mensagem/'
uri_premium_sorte = url_horoscopo + '/premium/sorte/'
uri_premium_amor = url_horoscopo + '/premium/amor/'
uri_premium_saude = url_horoscopo + '/premium/saude/'
uri_premium_mega_sena = url_horoscopo + '/premium/mega-sena/'


def informacoes_usuario():
    uri = 'http://127.0.0.1:8000/api/user'
    response = requests.get(uri, headers=headers)
    json_indentado(response)


def api_consumer(signo):
    uri = uri_premium + signo
    response = requests.get(uri, headers=headers)
    json_indentado(response)



api_consumer('meusigno')
