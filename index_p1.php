<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เลือกไพ่ Poker</title>

  <!-- Favicon -->
  <link rel="icon" href="chip.png" type="image/png">

  <!-- Meta tags for SEO -->
  <meta name="description" content="เล่นเกมโป๊กเกอร์ออนไลน์ง่ายๆ พร้อมเลือกไพ่จากตัวเลือกที่หลากหลาย">
  <meta name="keywords" content="Poker, เกมโป๊กเกอร์, เล่นโป๊กเกอร์ออนไลน์, ไพ่, เกมไพ่, Poker game">
  <meta name="author" content="Pe.NewZa">
  
  <!-- Open Graph for social media -->
  <meta property="og:title" content="เลือกไพ่ Poker">
  <meta property="og:description" content="เล่นเกมโป๊กเกอร์ออนไลน์ง่ายๆ พร้อมเลือกไพ่จากตัวเลือกที่หลากหลาย">
  <meta property="og:image" content="chip.png">
  <meta property="og:url" content="https://poker-07yu.onrender.com/">
  <meta property="og:type" content="website">
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="Poker">
  <meta name="twitter:title" content="เลือกไพ่ Poker">
  <meta name="twitter:description" content="เล่นเกมโป๊กเกอร์ออนไลน์ง่ายๆ พร้อมเลือกไพ่จากตัวเลือกที่หลากหลาย">
  <meta name="twitter:image" content="chip.png">
  
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


  <style>
    body { font-family: sans-serif; }
    .slot {
      width: 80px; height: 110px;
      border: 2px dashed #ccc;
      display: inline-block;
      margin: 5px;
      cursor: pointer;
    }
    .slot img { width: 100%; height: auto; }
    .slot.selected {
      border: 2px solid #007bff;
      box-shadow: 0 0 8px #007bff;
    }
    .card-img {
      width: 80px;
      border: 1px solid #ccc;
      border-radius: 6px;
      cursor: pointer;
    }
    .card-img.disabled {
      opacity: 0.3;
      pointer-events: none;
    }
    .card-pool.d-none { display: none; }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="text-center mb-4">เลือกไพ่ Poker & Pe.NewZa</h2>

  <div class="text-center mb-3">
    <label for="player_count">จำนวนผู้เล่น (รวมคุณ):</label>
    <select id="player_count" class="form-select d-inline-block w-auto">
      <?php for ($i = 2; $i <= 9; $i++) echo "<option value='$i'>$i คน</option>"; ?>
    </select>
  </div>

  <!-- ไพ่ผู้เล่น -->
  <div class="text-center mb-3">
    <div class="slot" data-name="my_card1"></div>
    <div class="slot" data-name="my_card2"></div>
  </div>

  <!-- ไพ่ Flop -->
  <div class="text-center mb-3">
    <h5>Flop</h5>
    <div class="slot" data-name="board_card1"></div>
    <div class="slot" data-name="board_card2"></div>
    <div class="slot" data-name="board_card3"></div>
  </div>

  <!-- Turn & River -->
  <div class="text-center mb-4">
    <h5>Turn & River</h5>
    <div class="slot" data-name="board_card4"></div>
    <div class="slot" data-name="board_card5"></div>
  </div>

  <!-- ปุ่ม -->
  <div class="text-center">
  <button class="btn btn-success" id="analyze-btn" onclick="analyze()" style="display:none">วิเคราะห์</button>

    <button class="btn btn-danger" onclick="resetAll()">ล้างค่า</button>
  </div>

  <!-- ซ่อน form -->
  <form id="card-form">
    <?php
      $names = ['my_card1','my_card2','board_card1','board_card2','board_card3','board_card4','board_card5'];
      foreach ($names as $n) echo "<input type='hidden' name='$n'>";
    ?>
    <input type="hidden" name="player_count" id="player_count_input">
  </form>

  <!-- ไพ่ทั้งหมด -->
  <div class="card-pool d-none">
    <?php
      $suits = ['clubs'=>'c', 'diamonds'=>'d', 'hearts'=>'h', 'spades'=>'s'];
      $ranks = ['2','3','4','5','6','7','8','9','T','J','Q','K','A'];
      $rank_map = ['T'=>'10', 'J'=>'jack', 'Q'=>'queen', 'K'=>'king', 'A'=>'ace'];
      foreach ($ranks as $r) {
        foreach ($suits as $suit_name => $s) {
          $display = $rank_map[$r] ?? $r;
          $file = strtolower("{$display}_of_{$suit_name}.png");
          echo "<img src='png/{$file}' class='card-img' data-value='{$r}{$s}' data-src='png/{$file}'>";
        }
      }
    ?>
  </div>
</div>

<!-- Modal เลือกไพ่ -->
<div class="modal fade" id="cardPickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">เลือกไพ่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body">
        <div class="card-pool-modal d-flex flex-wrap justify-content-center gap-2"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let selectedSlot = null;
const form = document.getElementById("card-form");

document.querySelectorAll(".slot").forEach(slot => {
  slot.addEventListener("click", () => {
    const name = slot.dataset.name;
    const input = form.querySelector(`input[name='${name}']`);

    if (input.value) {
      document.querySelector(`.card-img[data-value='${input.value}']`)?.classList.remove("disabled");
      input.value = "";
      slot.innerHTML = "";
      updateAnalyzeButton();
      return;
    }

    // ต้องเลือกไพ่ผู้เล่นก่อน
    const isMyCard = name === 'my_card1' || name === 'my_card2';
    const my1 = form.querySelector("input[name='my_card1']").value;
    const my2 = form.querySelector("input[name='my_card2']").value;
    if (!isMyCard && (!my1 || !my2)) {
      alert("กรุณาเลือกไพ่ของคุณ 2 ใบก่อน");
      return;
    }

    selectedSlot = slot;
    document.querySelectorAll(".slot").forEach(s => s.classList.remove("selected"));
    slot.classList.add("selected");

    // สร้าง popup ไพ่
    const modalCards = document.querySelector(".card-pool-modal");
    modalCards.innerHTML = '';
    document.querySelectorAll(".card-pool .card-img").forEach(card => {
      const clone = card.cloneNode(true);
      if (card.classList.contains("disabled")) {
        clone.classList.add("disabled");
      }
      modalCards.appendChild(clone);
    });

    const modal = new bootstrap.Modal(document.getElementById("cardPickerModal"));
    modal.show();
  });
});

// เลือกไพ่จาก modal
document.addEventListener("click", function (e) {
  if (!e.target.classList.contains("card-img") || e.target.classList.contains("disabled")) return;

  const card = e.target;
  const value = card.dataset.value;
  const src = card.dataset.src;
  const inputName = selectedSlot.dataset.name;
  const input = form.querySelector(`input[name='${inputName}']`);

  if (input.value) {
    document.querySelector(`.card-img[data-value='${input.value}']`)?.classList.remove("disabled");
  }

  input.value = value;
  selectedSlot.innerHTML = `<img src="${src}" class="w-100">`;
  document.querySelector(`.card-img[data-value='${value}']`)?.classList.add("disabled");

  const modal = bootstrap.Modal.getInstance(document.getElementById("cardPickerModal"));
  modal.hide();

  selectedSlot.classList.remove("selected");
  selectedSlot = null;

  updateAnalyzeButton();
});

function updateAnalyzeButton() {
  const my1 = form.querySelector("input[name='my_card1']").value;
  const my2 = form.querySelector("input[name='my_card2']").value;
  const btn = document.getElementById("analyze-btn");
  btn.style.display = (my1 && my2) ? 'inline-block' : 'none';
}

function resetAll() {
  document.querySelectorAll(".slot").forEach(s => {
    s.innerHTML = '';
    s.classList.remove("selected");
  });
  document.querySelectorAll("input[type=hidden]").forEach(i => i.value = '');
  document.querySelectorAll(".card-img").forEach(c => c.classList.remove("disabled"));
  selectedSlot = null;
  updateAnalyzeButton();
}

// ✅ ย้ายออกมาจาก resetAll()
function analyze() {
  document.getElementById("player_count_input").value = document.getElementById("player_count").value;
  const formData = new FormData(form);

  fetch("analyze.php", {
    method: "POST",
    body: JSON.stringify(Object.fromEntries(formData))
  })
  .then(res => {
    if (!res.ok) throw new Error("เกิดข้อผิดพลาดจากเซิร์ฟเวอร์");
    return res.text();
  })
  .then(html => {
    const resultModal = document.createElement("div");
    resultModal.className = "modal fade";
    resultModal.tabIndex = -1;
    resultModal.innerHTML = `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
          <div class="modal-header">
            <h5 class="modal-title">ผลวิเคราะห์ไพ่</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
          </div>
          <div class="modal-body">${html}</div>
        </div>
      </div>
    `;
    document.body.appendChild(resultModal);
    const modal = new bootstrap.Modal(resultModal);
    modal.show();
    modal._element.addEventListener("hidden.bs.modal", () => resultModal.remove());
  })
  .catch(err => {
    alert("❌ ไม่สามารถวิเคราะห์ได้: " + err.message);
    console.error("Analyze error:", err);
  });
}


function isHoleCardFilled() {
    const my1 = form.querySelector("input[name='my_card1']").value;
    const my2 = form.querySelector("input[name='my_card2']").value;
    return my1 && my2;
  }

</script>
</body>
</html>
