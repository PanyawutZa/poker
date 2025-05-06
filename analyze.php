<?php
function cardToHTML($card) {
    if (!is_string($card) || strlen($card) < 2 || strlen($card) > 3) {
        return '<span style="color:red">❌ ไพ่ไม่ถูกต้อง</span>';
    }

    $map = ['T'=>'10','J'=>'jack','Q'=>'queen','K'=>'king','A'=>'ace', '1'=>'ace'];
    $suits = ['s'=>'spades','h'=>'hearts','d'=>'diamonds','c'=>'clubs'];
    $validRanks = ['2','3','4','5','6','7','8','9','T','J','Q','K','A'];

    $r = strtoupper(substr($card, 0, 1));
    $s = strtolower(substr($card, 1, 1));

    if (!in_array($r, $validRanks)) {
        return '<span style="color:red">❌ อันดับไพ่ไม่ถูกต้อง</span>';
    }
    if (!isset($suits[$s])) {
        return '<span style="color:red">❌ ดอกไพ่ไม่ถูกต้อง</span>';
    }

    $rank = $map[$r] ?? $r;
    $suit = $suits[$s];
    $path = "png/{$rank}_of_{$suit}.png";
    $altPath = "images/{$rank}_of_{$suit}.png";

    if (file_exists($path)) {
        return "<img src='$path' height='90' style='margin:3px; border:1px solid #aaa; border-radius:6px' alt='$rank of $suit'>";
    } elseif (file_exists($altPath)) {
        return "<img src='$altPath' height='90' style='margin:3px; border:1px solid #aaa; border-radius:6px' alt='$rank of $suit'>";
    }
    return "<span style='color:red'>❌ ไม่พบไฟล์รูปภาพสำหรับ $rank of $suit</span>";
}

function waitingForHands($holeCards, $boardCards) {
    $all = array_filter(array_merge($holeCards, $boardCards), fn($c) => is_string($c) && strlen($c) >= 2);
    if (count($all) < 2) return "ข้อมูลไพ่ไม่ครบถ้วน";

    $ranks = array_map(fn($c) => strtoupper($c[0]), $all);
    $suits = array_map(fn($c) => strtolower($c[1]), $all);

    $rank_count = array_count_values($ranks);
    $suit_count = array_count_values($suits);

    $results = [];
    $rank_map = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'T'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14];
    $nums = array_map(fn($c) => $rank_map[strtoupper($c[0])] ?? 0, $all);
    $nums = array_values(array_filter($nums));

    // รองรับ A เป็น 1 สำหรับ Straight (A-2-3-4-5)
    if (in_array(14, $nums)) {
        $nums[] = 1;
    }
    $nums = array_unique($nums);
    sort($nums);

    // Royal Flush
    foreach ($suit_count as $suit => $count) {
        if ($count >= 5) {
            $same_suit_cards = array_filter($all, fn($c) => strtolower($c[1]) === $suit);
            $same_suit_nums = array_map(fn($c) => $rank_map[strtoupper($c[0])] ?? 0, $same_suit_cards);
            if (in_array(14, $same_suit_nums)) $same_suit_nums[] = 1;
            $same_suit_nums = array_unique(array_filter($same_suit_nums));
            sort($same_suit_nums);
            for ($i = 0; $i <= count($same_suit_nums) - 5; $i++) {
                $slice = array_slice($same_suit_nums, $i, 5);
                if (count($slice) === 5 && $slice[0] === 10 && $slice[4] === 14) {
                    $results[] = "รอยัลฟลัช";
                }
            }
        }
    }

    // Straight Flush
    foreach ($suit_count as $suit => $count) {
        if ($count >= 5) {
            $same_suit_cards = array_filter($all, fn($c) => strtolower($c[1]) === $suit);
            $same_suit_nums = array_map(fn($c) => $rank_map[strtoupper($c[0])] ?? 0, $same_suit_cards);
            if (in_array(14, $same_suit_nums)) $same_suit_nums[] = 1;
            $same_suit_nums = array_unique(array_filter($same_suit_nums));
            sort($same_suit_nums);
            for ($i = 0; $i <= count($same_suit_nums) - 5; $i++) {
                $slice = array_slice($same_suit_nums, $i, 5);
                if (count($slice) === 5 && $slice[4] - $slice[0] === 4 && !($slice[0] === 10 && $slice[4] === 14)) {
                    $results[] = "สเตรทฟลัช";
                }
            }
        }
    }

    // Four of a Kind
    $quads = array_keys(array_filter($rank_count, fn($v) => $v >= 4));
    if (count($quads) >= 1) {
        $results[] = "ไพ่สี่ใบเหมือนกัน";
    }

    // Full House
    $trips = array_keys(array_filter($rank_count, fn($v) => $v >= 3));
    $pairs = array_keys(array_filter($rank_count, fn($v) => $v >= 2));
    if (count($trips) >= 1 && (count($pairs) >= 2 || (count($pairs) === 1 && $rank_count[$pairs[0]] >= 2))) {
        $results[] = "ฟูลเฮาส์";
    }

    // Flush
    foreach ($suit_count as $suit => $count) {
        if ($count >= 5) {
            $results[] = "ฟลัช";
        }
    }

    // Straight
    $isStraight = false;
    for ($i = 0; $i <= count($nums) - 5; $i++) {
        $slice = array_slice($nums, $i, 5);
        if (count($slice) === 5 && $slice[4] - $slice[0] === 4 && count(array_unique($slice)) === 5) {
            $results[] = "สเตรท";
            $isStraight = true;
            break;
        }
    }

    // Three of a Kind
    if (count($trips) >= 1 && !in_array("ฟูลเฮาส์", $results)) {
        $results[] = "ไพ่ตอง";
    }

    // Two Pair
    if (count($pairs) >= 2 && !in_array("ฟูลเฮาส์", $results)) {
        $results[] = "สองคู่";
    }

    // One Pair
    if (count($pairs) === 1 && !in_array("สองคู่", $results) && !in_array("ฟูลเฮาส์", $results)) {
        $results[] = "หนึ่งคู่";
    }

    // High Card (ถ้าไม่มีอะไรเลย)
    if (count($results) === 0) {
        $results[] = "ไพ่สูงสุด";
    }

    // รอ Flush
    foreach ($suit_count as $suit => $count) {
        if ($count === 4) $results[] = "รอฟลัช";
        if ($count === 3) $results[] = "มีโอกาสฟลัช (3 ใบสีเดียวกัน)";
    }

    // รอ Straight
    if (!$isStraight) {
        for ($i = 0; $i <= count($nums) - 4; $i++) {
            $slice = array_slice($nums, $i, 4);
            if (count($slice) === 4 && $slice[3] - $slice[0] <= 4 && count(array_unique($slice)) === 4) {
                $results[] = "รอสเตรท";
                break;
            }
        }
    }

    return implode(' + ', array_unique($results));
}

function countOutsAndPercent($waiting_for, $cardsInPlay, $playerCount) {
    $standardOuts = [
        'รอยัลฟลัช' => 1,
        'สเตรทฟลัช' => 1,
        'ไพ่สี่ใบเหมือนกัน' => 1,
        'ฟูลเฮาส์' => 6, // 2 สำหรับ Trips + 4 สำหรับ Pair
        'ฟลัช' => 0,
        'รอฟลัช' => 9,
        'มีโอกาสฟลัช (3 ใบสีเดียวกัน)' => 9,
        'สเตรท' => 0,
        'รอสเตรท' => 8,
        'ไพ่ตอง' => 2,
        'สองคู่' => 4,
        'หนึ่งคู่' => 2,
        'ไพ่สูงสุด' => 3
    ];

    $outs = 0;
    $conditions = explode(' + ', $waiting_for);
    $uniqueOuts = [];
    foreach ($conditions as $condition) {
        if (isset($standardOuts[$condition])) {
            $uniqueOuts[$condition] = $standardOuts[$condition];
        }
    }
    $outs = array_sum($uniqueOuts);

    $known = count(array_filter($cardsInPlay));
    $unknown = max(52 - $known, 1);

    if ($known >= 7) {
        return [0, 0, 0, 0, 0];
    }

    $percentFlopToTurn = ($outs > 0 && $unknown > 0) ? round(($outs / $unknown) * 100, 1) : 0;
    $percentTurnToRiver = ($outs > 0 && $unknown > 1) ? round(($outs / max($unknown - 1, 1)) * 100, 1) : 0;
    $percentFlopToRiver = ($outs > 0 && $unknown > 1)
        ? round((1 - pow((max($unknown - $outs, 0) / $unknown), 2)) * 100, 1)
        : 0;

    $adjusted = round($percentFlopToRiver * pow(0.95, max($playerCount - 2, 0)), 1);
    $adjusted = max($adjusted, 0);

    return [$outs, $percentFlopToRiver, $percentFlopToTurn, $percentTurnToRiver, $adjusted];
}

function adjustPercent($base, $players) {
    $adjusted = $base - (($players - 2) * 5);
    return max($adjusted, 1);
}

function aiAdvice($status) {
    return match($status) {
        'รอยัลฟลัช' => 'มือสูงสุด ไม่มีอะไรต้องกลัว All-in ได้เลย',
        'สเตรทฟลัช' => 'มือสูงสุด ไม่ต้องลังเล All-in ได้เลย',
        'ไพ่สี่ใบเหมือนกัน' => 'ควรใช้โอกาสกดดันผู้เล่นทันที',
        'ฟูลเฮาส์' => 'ควรเล่นเชิงรุกเต็มที่',
        'ฟลัช' => 'มือแข็งแรง ใช้จังหวะเพิ่มเดิมพัน',
        'สเตรท' => 'มือเรียง เล่นได้ทั้งรุกและป้องกัน',
        'ไพ่ตอง' => 'ควรเพิ่มเดิมพันเพื่อดึงกำไร หรือขู่ฝ่ายตรงข้าม',
        'สองคู่' => 'มือคุณพอใช้ หากไม่มีแรงกดดันจากการเร่งเดิมพัน ควรเล่นต่อ',
        'หนึ่งคู่' => 'คุณมีคู่เดียว อาจรอ Turn/River ให้ติดตองหรือสเตรท',
        'ไพ่สูงสุด' => 'คุณยังไม่มีไพ่ที่แข็งแรง หากมีผู้เล่นเร่งเดิมพัน ควรหมอบ',
        'รอฟลัช' => 'มีโอกาสลุ้นฟลัช ควรพิจารณาต่อไปตามงบประมาณ',
        'มีโอกาสฟลัช (3 ใบสีเดียวกัน)' => 'มีโอกาสลุ้นฟลัช ควรดู Turn',
        'รอสเตรท' => 'มีโอกาสลุ้นสเตรท ควรพิจารณาต่อไป',
        default => 'ยังวิเคราะห์ไม่ได้',
    };
}

function evaluateHoleCards($me) {
    if (!isset($me[0]) || !isset($me[1]) || strlen($me[0]) < 2 || strlen($me[1]) < 2) {
        return 'ข้อมูลไพ่ในมือไม่ครบ';
    }

    $r1 = strtoupper($me[0][0]);
    $r2 = strtoupper($me[1][0]);
    $s1 = strtolower($me[0][1]);
    $s2 = strtolower($me[1][1]);

    $rank_order = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'T'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14];
    $val1 = $rank_order[$r1] ?? 0;
    $val2 = $rank_order[$r2] ?? 0;

    $gap = abs($val1 - $val2);
    if ($val1 === 14 || $val2 === 14) {
        $altGap = min(abs($val1 - 1 - $val2), abs($val2 - 1 - $val1));
        $gap = min($gap, $altGap);
    }

    if ($r1 === $r2) {
        $pairRank = $r1;
        if (in_array($pairRank, ['A','K','Q','J','T'])) {
            return "ไพ่คู่สูง ($pairRank) มีโอกาสดีมาก ควรเล่นเชิงรุก";
        } elseif (in_array($pairRank, ['9','8','7','6','5'])) {
            return "ไพ่คู่กลาง ($pairRank) เล่นได้ดีโดยเฉพาะในตำแหน่งท้าย";
        } else {
            return "ไพ่คู่ต่ำ ($pairRank) ควรเล่นเฉพาะกรณีเห็น Flop ราคาถูก หรือในตำแหน่งดี";
        }
    }

    if ($s1 === $s2) {
        if ($gap <= 1) {
            return "ไพ่เชื่อมต่อดอกเดียวกัน (เช่น $r1$r2 suited connector) มีโอกาสลุ้นสเตรทหรือฟลัช";
        }
        return "ไพ่ดอกเดียวกัน มีโอกาสลุ้นฟลัช";
    }

    if ($gap == 0) return "ไพ่ตัวเลขเท่ากันแต่คนละดอก";
    if ($gap == 1) return "ไพ่เรียงใกล้กัน มีโอกาสลุ้นสเตรท";
    if ($gap == 2) return "ไพ่ห่างไม่มาก พอมีโอกาสลุ้นสเตรท";

    return "ไพ่ธรรมดา ไม่ใช่คู่หรือเรียง ลุ้น Flop เป็นหลัก";
}

function getWaitingCards($holeCards, $boardCards, $playerCount = 2, &$opponentRisk = '') {
    $rankMap = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'T'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14];

    $all = array_filter(array_merge($holeCards, $boardCards), fn($c) => is_string($c) && strlen($c) >= 2);
    $allRanks = array_map(fn($c) => strtoupper($c[0]), $all);
    $allSuits = array_map(fn($c) => strtolower($c[1]), $all);
    $used = array_map('strtolower', $all);
    $holeRanks = array_map(fn($c) => strtoupper($c[0]), $holeCards);
    $holeNums = array_map(fn($r) => $rankMap[$r] ?? 0, $holeRanks);

    $deck = [];
    foreach (['s','h','d','c'] as $suit) {
        foreach (array_keys($rankMap) as $rank) {
            $card = strtolower($rank . $suit);
            if (!in_array($card, $used)) $deck[] = $card;
        }
    }

    $waitingCards = [];
    $rankCounts = array_count_values($allRanks);
    $suitCounts = array_count_values($allSuits);
    $nums = array_unique(array_map(fn($r) => $rankMap[$r] ?? 0, $allRanks));
    sort($nums);

    // 1. Royal Flush / Straight Flush Draw
    foreach ($suitCounts as $suit => $count) {

        if ($count >= 4) {
            $same_suit_cards = array_filter($all, fn($c) => strtolower($c[1]) === $suit);
            $same_suit_nums = array_map(fn($c) => $rankMap[strtoupper($c[0])] ?? 0, $same_suit_cards);
            if (in_array(14, $same_suit_nums)) $same_suit_nums[] = 1;
            $same_suit_nums = array_unique(array_filter($same_suit_nums));
            sort($same_suit_nums);
            for ($i = 0; $i <= count($same_suit_nums) - 4; $i++) {
                $slice = array_slice($same_suit_nums, $i, 4);
                if (count($slice) === 4 && $slice[3] - $slice[0] <= 4) {
                    for ($j = $slice[0] - 1; $j <= $slice[3] + 1; $j++) {
                        if (!in_array($j, $same_suit_nums)) {
                            foreach ($rankMap as $rk => $val) {
                                if ($val == $j && in_array($rk, ['T','J','Q','K','A'])) {
                                    foreach (['s','h','d','c'] as $s) {
                                        $card = strtolower($rk . $s);
                                        if ($s === $suit && !in_array($card, $used)) {
                                            $waitingCards[] = strtoupper($card);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // 2. Four of a Kind Draw
    $trips = array_keys(array_filter($rankCounts, fn($v) => $v === 3));
    if (count($trips) >= 1) {
        foreach ($trips as $rank) {
            foreach ($deck as $c) {
                if (strtoupper($c[0]) === $rank) {
                    $waitingCards[] = strtoupper($c);
                }
            }
        }
    }

    // 3. Full House Draw (จาก Two Pair หรือ Trips)
    $pairRanks = array_keys(array_filter($rankCounts, fn($v) => $v >= 2));
    if (count($pairRanks) >= 2 || (count($trips) >= 1 && count($pairRanks) >= 1)) {
        foreach ($pairRanks as $rank) {
            foreach ($deck as $c) {
                if (strtoupper($c[0]) === $rank) {
                    $waitingCards[] = strtoupper($c);
                }
            }
        }
    }

    // 4. Flush Draw
    foreach ($suitCounts as $suit => $count) {
        if ($count >= 3) {
            foreach ($deck as $c) {
                if (substr($c, 1, 1) === $suit) {
                    $waitingCards[] = strtoupper($c);
                }
            }
        }
    }

    // 5. Straight Draw (Open-ended, Gutshot, A-2-3-4-5)
    $neededRanks = [];
    for ($i = 0; $i <= count($nums) - 4; $i++) {
        $win = array_slice($nums, $i, 4);
        if (count($win) === 4 && $win[3] - $win[0] <= 4) {
            for ($j = $win[0] - 1; $j <= $win[3] + 1; $j++) {
                if (!in_array($j, $nums)) {
                    foreach ($rankMap as $rk => $val) {
                        if ($val == $j) $neededRanks[] = $rk;
                    }
                }
            }
        }
    }
    if (in_array(2, $nums) && in_array(3, $nums) && in_array(4, $nums) && in_array(5, $nums) && !in_array(14, $nums)) {
        $neededRanks[] = 'A';
    }
    foreach ($deck as $c) {
        if (in_array(strtoupper($c[0]), $neededRanks)) {
            $waitingCards[] = strtoupper($c);
        }
    }

    // 6. Three of a Kind Draw
    $pairs = array_keys(array_filter($rankCounts, fn($v) => $v === 2));
    if (count($pairs) >= 1) {
        foreach ($pairs as $rank) {
            foreach ($deck as $c) {
                if (strtoupper($c[0]) === $rank) {
                    $waitingCards[] = strtoupper($c);
                }
            }
        }
    }

    // 7. Two Pair Draw
    if (count($pairs) === 1) {
        $rank = $pairs[0];
        foreach ($deck as $c) {
            $r = strtoupper($c[0]);
            if ($r === $rank || in_array($r, $holeRanks)) {
                $waitingCards[] = strtoupper($c);
            }
        }
    }

    // 8. One Pair Draw (จาก High Card หรือ Overcards)
    if (count($waitingCards) === 0) {
        $boardNums = array_map(fn($c) => $rankMap[strtoupper($c[0])] ?? 0, $boardCards);
        $maxBoard = count($boardNums) > 0 ? max($boardNums) : 0;
        foreach ($deck as $c) {
            $r = strtoupper($c[0]);
            $val = $rankMap[$r] ?? 0;
            if (in_array($r, $holeRanks) || ($val > $maxBoard)) {
                $waitingCards[] = strtoupper($c);
            }
        }
    }

    // วิเคราะห์ความเสี่ยงจากบอร์ด
    $risk = [];
    $boardRanks = array_map(fn($c) => strtoupper($c[0]), $boardCards);
    $boardNums = array_unique(array_map(fn($r) => $rankMap[$r] ?? 0, $boardRanks));
    sort($boardNums);
    $boardSuits = array_map(fn($c) => strtolower($c[1]), $boardCards);
    $boardSuitCounts = array_count_values($boardSuits);

    // Royal Flush / Straight Flush / Flush
    foreach ($boardSuitCounts as $suit => $count) {
        if ($count >= 5) {
            $risk[] = "บอร์ดฟลัชสำเร็จ ($count ใบ)";
            $sameSuitCards = array_filter($boardCards, fn($c) => strtolower($c[1]) === $suit);
            $numsSameSuit = array_map(fn($c) => $rankMap[strtoupper($c[0])] ?? 0, $sameSuitCards);
            sort($numsSameSuit);
            for ($i = 0; $i <= count($numsSameSuit) - 5; $i++) {
                $slice = array_slice($numsSameSuit, $i, 5);
                if ($slice[4] - $slice[0] === 4) {
                    if ($slice[0] === 10 && $slice[4] === 14) $risk[] = "บอร์ดมี Royal Flush";
                    else $risk[] = "บอร์ดมี Straight Flush";
                }
            }
        } elseif ($count === 4) {
            $risk[] = "บอร์ดมี 4 ใบสีเดียวกัน: มีโอกาสฟลัช";
        } elseif ($count === 3) {
            $risk[] = "บอร์ดมี 3 ใบสีเดียวกัน: รอฟลัช";
        }
    }

    // Straight
    for ($i = 0; $i <= count($boardNums) - 5; $i++) {
        if ($boardNums[$i + 4] - $boardNums[$i] === 4) {
            $risk[] = "บอร์ดเรียง 5 ใบ: มีโอกาสติดสเตรท";
            break;
        }
    }

    // รอ Straight
    for ($i = 0; $i <= count($boardNums) - 4; $i++) {
        $slice = array_slice($boardNums, $i, 4);
        if (count($slice) === 4 && $slice[3] - $slice[0] <= 4) {
            $risk[] = "บอร์ดมีโอกาสรอสเตรท";
            break;
        }
    }

    // Full House
    $brCounts = array_count_values($boardRanks);
    if (in_array(3, $brCounts) && in_array(2, $brCounts)) {
        $risk[] = "บอร์ดมีฟูลเฮาส์";
    }

    // Four of a Kind
    if (in_array(4, $brCounts)) {
        $risk[] = "บอร์ดมีไพ่สี่ใบเหมือนกัน";
    }

    // Three of a Kind
    foreach ($brCounts as $r => $cnt) {
        if ($cnt === 3) $risk[] = "บอร์ดมีตอง ($r)";
    }

    // Two Pair
    $boardPairs = array_keys(array_filter($brCounts, fn($v) => $v === 2));
    if (count($boardPairs) >= 2) {
        $risk[] = "บอร์ดมีสองคู่";
    }

    // One Pair
    if (count($boardPairs) === 1) {
        $risk[] = "บอร์ดมีคู่ (" . $boardPairs[0] . ")";
    }

    // ไพ่สูงของเราต่ำกว่าไพ่สูงสุดบนบอร์ด
    if (count($boardNums) > 0 && max($holeNums) < max($boardNums)) {
        $risk[] = "ไพ่สูงของคุณต่ำกว่าไพ่สูงสุดบนบอร์ด (อาจมี overpair)";
    }

    // Double Gutshot Draw
    if (count($nums) >= 4) {
        for ($i = 0; $i <= count($nums) - 3; $i++) {
            if ($nums[$i + 2] - $nums[$i] === 4 && !in_array($nums[$i] + 2, $nums)) {
                $risk[] = "อาจมีผู้เล่นรอ Double Gutshot Draw";
                break;
            }
        }
    }

    // ผู้เล่นจำนวนมาก
    if ($playerCount >= 4) $risk[] = "จำนวนผู้เล่นมาก (≥4): ความเสี่ยงสูง";

    $opponentRisk = count($risk)
        ? "⚠️ ความเสี่ยงจากผู้เล่นอื่น: " . implode(" / ", $risk)
        : "ไม่มีความเสี่ยงที่เด่นชัดจากบอร์ด";

    $waitingCards = array_unique($waitingCards);
    sort($waitingCards);
    return $waitingCards;
}

function evaluate($cards, $playerCount = 2, &$opponent_warning = '') {
    $hole = array_slice($cards, 0, 2);
    $board = array_slice($cards, 2);
    $waiting = waitingForHands($hole, $board);
    if ($waiting === "ข้อมูลไพ่ไม่ครบถ้วน") {
        return ["ข้อมูลไม่ครบ", "กรุณาใส่ไพ่ให้ครบ", 0, $waiting, []];
    }
    $waitingCards = getWaitingCards($hole, $board, $playerCount, $opponent_warning);

    $handStrength = [
        'รอยัลฟลัช' => 99,
        'สเตรทฟลัช' => 98,
        'ไพ่สี่ใบเหมือนกัน' => 95,
        'ฟูลเฮาส์' => 90,
        'ฟลัช' => 85,
        'สเตรท' => 80,
        'ไพ่ตอง' => 75,
        'สองคู่' => 60,
        'หนึ่งคู่' => 40,
        'ไพ่สูงสุด' => 10
    ];

    $allDetected = explode(' + ', $waiting);
    $status = 'ไพ่สูงสุด';
    $basePercent = 10;

    foreach ($handStrength as $hand => $percent) {
        if (in_array($hand, $allDetected)) {
            $status = $hand;
            $basePercent = $percent;
            break;
        }
    }

    $advice = aiAdvice($status);
    $percent = adjustPercent($basePercent, $playerCount);
    return [$status, $advice, $percent, $waiting, $waitingCards];
}

function calculateWinningOdds($outs, $knownCards = 5, $playerCount = 2) {
    $remainingFlopToTurn = max(52 - $knownCards, 1);
    $remainingTurnToRiver = max($remainingFlopToTurn - 1, 1);

    if ($remainingFlopToTurn <= 0 || $outs <= 0) {
        return 0;
    }

    $p1 = $outs / $remainingFlopToTurn;
    $p2 = $outs / $remainingTurnToRiver;
    $flopToRiver = 1 - (1 - $p1) * (1 - $p2);

    $playerAdjustment = 1 - pow((1 - $flopToRiver), max($playerCount - 1, 1));
    $adjusted = $flopToRiver * (1 - $playerAdjustment * 0.5);
    $adjusted = max($adjusted, 0);

    return round($adjusted * 100, 1);
}

$data = json_decode(file_get_contents("php://input"), true);
$me = array_filter([$data['my_card1'] ?? '', $data['my_card2'] ?? ''], fn($c) => is_string($c) && strlen($c) >= 2);
if (count($me) < 2) {
    echo "<p style='color:red'>❌ กรุณาใส่ไพ่ในมือให้ครบ 2 ใบ</p>";
    exit;
}

$flop = array_filter([
    $data['board_card1'] ?? '',
    $data['board_card2'] ?? '',
    $data['board_card3'] ?? ''
], fn($c) => is_string($c) && strlen($c) >= 2);

$turn = isset($data['board_card4']) && is_string($data['board_card4']) && strlen($data['board_card4']) >= 2 ? $data['board_card4'] : '';
$river = isset($data['board_card5']) && is_string($data['board_card5']) && strlen($data['board_card5']) >= 2 ? $data['board_card5'] : '';
$playerCount = max(intval($data['player_count'] ?? 2), 2);

$first = array_merge($me, $flop);
$holeCards = $me;
$boardCards = array_filter([$data['board_card1'] ?? '', $data['board_card2'] ?? '', $data['board_card3'] ?? '', $turn, $river], fn($c) => is_string($c) && strlen($c) >= 2);
$all = array_merge($holeCards, $boardCards);
$allRanks = array_map(fn($c) => strtoupper($c[0]), $all);
$allSuits = array_map(fn($c) => strtolower($c[1]), $all);

$allRanks = array_filter($allRanks);
$allSuits = array_filter($allSuits);

$boardOnly = array_filter([$data['board_card1'] ?? '', $data['board_card2'] ?? '', $data['board_card3'] ?? '', $turn, $river], fn($c) => is_string($c) && strlen($c) >= 2);

$opponent_warning = '';
[$status, $advice, $percent, $waiting_for, $waiting_cards] = evaluate(count($all) >= 5 ? $all : $first, $playerCount, $opponent_warning);

$ai_tip = aiAdvice($status);
$hole_eval = evaluateHoleCards($me);
$hasBoard = count(array_filter($flop)) >= 3;
[$outs, $percentCombo, $percentTurn, $percentRiver, $adjCombo] = countOutsAndPercent($waiting_for, $all, $playerCount);
$calculatedPercent = calculateWinningOdds($outs, count(array_filter($all)), $playerCount);

header('Content-Type: text/html; charset=utf-8');
echo "<div><strong>ไพ่ของคุณ:</strong><br>" . cardToHTML($me[0]) . cardToHTML($me[1]) . "</div>";
echo "<p>💡 ประเมินไพ่ในมือ: <strong>$hole_eval</strong></p><hr>";
$flop_safe = array_map(fn($c) => cardToHTML($c), $flop);
echo "<div><strong>Flop:</strong><br>" . implode('', $flop_safe) . "</div>";

$turnHTML = $turn ? cardToHTML($turn) : '';
$riverHTML = $river ? cardToHTML($river) : '';
if ($turnHTML || $riverHTML) {
    echo "<hr><div><strong>Turn/River:</strong><br>$turnHTML$riverHTML</div>";
}

if ($hasBoard) {
    $knownCount = count(array_filter($all));
    $calculatedPercent = ($outs == 0 && $knownCount >= 7) ? adjustPercent(match($status) {
        'รอยัลฟลัช' => 99,
        'สเตรทฟลัช' => 98,
        'ไพ่สี่ใบเหมือนกัน' => 95,
        'ฟูลเฮาส์' => 90,
        'ฟลัช' => 85,
        'สเตรท' => 80,
        'ไพ่ตอง' => 75,
        'สองคู่' => 60,
        'หนึ่งคู่' => 40,
        'ไพ่สูงสุด' => 10
    }, $playerCount) : calculateWinningOdds($outs, $knownCount, $playerCount);

    $icon = '⚪️';
    $color = 'gray';
    if ($calculatedPercent >= 70) {
        $color = 'green'; $icon = '✅';
    } elseif ($calculatedPercent >= 30) {
        $color = 'orange'; $icon = '⚠️';
    } else {
        $color = 'red'; $icon = '❌';
    }

    if (count($all) >= 7) {
        $adjCombo = $calculatedPercent;
        echo "<hr><h5>🎯 สถานะหลังเปิดครบ 5 ใบ: <strong>$status</strong> ($icon <span style='color:$color'>📊 $adjCombo%</span> เมื่อมีผู้เล่น $playerCount คน)</h5>";
        echo "<p>📌 ไพ่เปิดครบแล้ว ไม่มีไพ่ให้ลุ้นอีก</p>";
        echo "<p>👥 $opponent_warning</p>";
    } else {
        echo "<hr><h5>🎯 สถานะ: <strong>$status</strong> ($icon <span style='color:$color'>📊 $calculatedPercent%</span> เมื่อมีผู้เล่น $playerCount คน)</h5>";
        echo "<p>🎓 คำแนะนำ: <strong>$advice</strong></p>";
        echo "<p>🧠 วิเคราะห์เพิ่มเติม: <em>$ai_tip</em></p>";
        echo "<p>🔮 สิ่งที่คุณยังลุ้นอยู่: <strong>$waiting_for</strong><br>
        💥 จำนวนไพ่ที่ลุ้น (Outs): $outs ใบ<br>
        🎯 โอกาสออก: Flop→Turn: <strong>$percentTurn%</strong> | Turn→River: <strong>$percentRiver%</strong> | รวม: <strong>$percentCombo</strong>%<br>";

        if (count($waiting_cards) > 0) {
            echo "<p>🃏 รายชื่อไพ่ที่รอ:<br>" . implode('', array_map('cardToHTML', $waiting_cards)) . "</p>";
        } else {
            echo "<p>🃏 รายชื่อไพ่ที่รอ: <span style='color:gray'>ไม่มีไพ่ให้ลุ้นเพิ่มเติม</span></p>";
        }
        echo "<p>👥 $opponent_warning</p>";
    }
} else {
    echo "<p style='color:gray'>💡 ยังไม่มีไพ่บนบอร์ดเพียงพอ (Flop) — กรุณาเลือกไพ่เพิ่มเพื่อวิเคราะห์สถานะเกม</p>";
}
?>