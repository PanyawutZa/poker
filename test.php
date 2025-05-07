<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>ทดสอบเสียงทั้งหมด</title>
</head>
<body>
  <h2>เลือกเสียงภาษาไทย</h2>
  <select id="voiceSelect"></select>
  <br><br>
  <textarea id="text" rows="4" cols="50">สวัสดีครับ นี่คือระบบอ่านข้อความภาษาไทย</textarea>
  <br>
  <button onclick="speak()">🔊 พูด</button>

  <script>
    const synth = window.speechSynthesis;
    const voiceSelect = document.getElementById("voiceSelect");
    let voices = [];

    function populateVoices() {
      voices = synth.getVoices();
      voiceSelect.innerHTML = '';
      voices.forEach((voice, i) => {
        const option = document.createElement("option");
        option.value = i;
        option.textContent = `${voice.name} (${voice.lang})`;
        voiceSelect.appendChild(option);
      });
    }

    populateVoices();
    if (speechSynthesis.onvoiceschanged !== undefined) {
      speechSynthesis.onvoiceschanged = populateVoices;
    }

    function speak() {
      const text = document.getElementById("text").value;
      const utterance = new SpeechSynthesisUtterance(text);
      const selectedVoice = voices[voiceSelect.value];
      utterance.voice = selectedVoice;
      utterance.lang = selectedVoice.lang;
      utterance.rate = 1;
      utterance.pitch = 1;
      utterance.volume = 1;
      synth.cancel();
      synth.speak(utterance);
    }
  </script>
</body>
</html>
