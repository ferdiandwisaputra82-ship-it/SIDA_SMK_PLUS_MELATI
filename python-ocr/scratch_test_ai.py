import os
import requests
import json

# Read env file
def _read_env(path):
    env_vars = {}
    if os.path.exists(path):
        with open(path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    k, _, v = line.partition("=")
                    env_vars[k.strip()] = v.strip().strip('"').strip("'")
    return env_vars

env1 = _read_env("python-ocr/.env")
env2 = _read_env(".env")

groq_key = env1.get("GROQ_API_KEY") or env2.get("GROQ_API_KEY") or ""
gemini_key = env1.get("GEMINI_API_KEY") or env2.get("GEMINI_API_KEY") or ""

print("==============================================")
print(f"1. TESTING GROQ API KEY: [{groq_key[:15]}...]")
print("==============================================")

if groq_key:
    url = "https://api.groq.com/openai/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {groq_key}",
        "Content-Type": "application/json"
    }
    # Test with text model llama-3.3-70b-versatile
    payload = {
        "model": "llama-3.3-70b-versatile",
        "messages": [{"role": "user", "content": "Return JSON: {\"status\": \"ok\"}"}],
        "temperature": 0.0
    }
    try:
        r = requests.post(url, json=payload, headers=headers, timeout=10)
        print(f"Groq HTTP Status: {r.status_code}")
        print(f"Groq Response: {r.text[:300]}")
    except Exception as e:
        print(f"Groq Error: {e}")
else:
    print("GROQ_API_KEY is empty!")

print("\n==============================================")
print(f"2. TESTING GEMINI API KEY: [{gemini_key[:15]}...]")
print("==============================================")

if gemini_key:
    models = ["gemini-1.5-flash", "gemini-2.5-flash", "gemini-2.0-flash"]
    for m in models:
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{m}:generateContent?key={gemini_key}"
        payload = {
            "contents": [{"parts": [{"text": "Return JSON: {\"status\": \"ok\"}"}]}]
        }
        try:
            r = requests.post(url, json=payload, timeout=10)
            print(f"Gemini [{m}] HTTP Status: {r.status_code}")
            print(f"Gemini [{m}] Response: {r.text[:250]}")
            if r.status_code == 200:
                print(f"--> SUCCESS ON {m}!")
                break
        except Exception as e:
            print(f"Gemini [{m}] Error: {e}")
else:
    print("GEMINI_API_KEY is empty!")
