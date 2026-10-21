<?php
error_reporting(0);
date_default_timezone_set("Asia/Dhaka");
define("BOT_TOKEN", "8612281481:AAHgwhQMdFuiwTn0oUH678bxT9CQHxZyRyI");
define("ADMIN_ID", "8885609920");

function req($m,$p = []) {
    $ch = curl_init("https://api.telegram.org/bot" . BOT_TOKEN . "/" . $m);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($p));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

function ld($f) {
    if (file_exists($f)) {
        $d = json_decode(@file_get_contents($f), true);
        if (is_array($d)) return$d;
    }
    return [];
}

function sv($f,$d) {
    @file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
}

function mainKb() {
    return [
        "keyboard" => [
            [["text" => "💼 কাজ শুরু করুন"], ["text" => "📊 প্রোফাইল"]],
            [["text" => "💸 উত্তোলন (Withdraw)"], ["text" => "👥 রেফার করুন"]],
            [["text" => "👨‍💻 এডমিন সাপোর্ট"]]
        ],
        "resize_keyboard" => true
    ];
}

function cancelKb() {
    return [
        "keyboard" => [
            [["text" => "❌ বাতিল (Cancel)"]]
        ],
        "resize_keyboard" => true
    ];
}

function sendSubButtons($chat_id) {$inline_kb = [
        "inline_keyboard" => [
            [["text" => "📢 অফিশিয়াল চ্যানেল", "url" => "https://t.me/sumon1835"]],
            [["text" => "💳 পেমেন্ট প্রুফ চ্যানেল", "url" => "https://t.me/sumon12357r"]],
            [["text" => "✅ জয়েন করেছি (Check)", "callback_data" => "check_join"]]
        ]
    ];
    req("sendMessage", [
        "chat_id" => $chat_id, 
        "text" => "⚠️ **বটটি ব্যবহার করতে আমাদের ২টি চ্যানেলে জয়েন করা বাধ্যতামূলক!**\n\nনিচের চ্যানেল দুটিতে জয়েন করে নিচের **'✅ জয়েন করেছি'** বাটনে ক্লিক করুন:", 
        "parse_mode" => "Markdown", 
        "reply_markup" => $inline_kb
    ]);
}

$of = 0;
echo ">>> BOT STARTED SUCCESSFULLY! <<<\n";

while (true) {
    try {
        $res = req("getUpdates", ["offset" => $of, "timeout" => 0, "allowed_updates" => ["message", "callback_query"]]);

        if (isset($res["result"]) && is_array($res["result"])) {
            foreach ($res["result"] as $up) {
                $of =$up["update_id"] + 1;
                $us = ld("users.json");
                $ts = ld("tasks.json");
                $cfg = ld("config.json");
                if (!isset($cfg["password"])) {
                    $cfg["password"] = "Sumon@21";
                    sv("config.json", $cfg);
                }

                if (isset($up["callback_query"])) {
                    $cq =$up["callback_query"];
                    $c = (string)$cq["from"]["id"];
                    $cq_id =$cq["id"];
                    $data =$cq["data"] ?? "";

                    if ($data === "check_join") {
                        req("answerCallbackQuery", ["callback_query_id" => $cq_id, "text" => "✅ ধন্যবাদ! ড্যাশবোর্ডে প্রবেশ করছেন।"]);
                        @req("deleteMessage", ["chat_id" => $c, "message_id" => $cq["message"]["message_id"]]);
                        req("sendMessage", ["chat_id" => $c, "text" => "🤖 **FB SELL BOT** — অফিশিয়াল ড্যাশবোর্ডে আপনাকে স্বাগতম!\n\n👇 *নিচে থেকে যেকোনো সেবায় যেতে বাটনে ট্যাপ করুন:*", "parse_mode" => "Markdown", "reply_markup" => mainKb()]);
                    }
                    continue;
                }

                if (isset($up["message"])) {
                    $m =$up["message"];
                    $c = (string)$m["chat"]["id"];
                    $t = trim($m["text"] ?? "");
                    $first_name =$m["from"]["first_name"] ?? "User";

                    if (!isset($us[$c])) {$us[$c] = ["name" => $first_name, "balance" => 0.00, "pending" => 0, "approved" => 0, "processing" => 0.00, "refers" => 0, "referred_by" => "", "state" => "none", "temp_uid" => ""];
                        sv("users.json", $us);
                    }

                    if (strpos($t, "/start") === 0) {
                        $param = trim(str_replace("/start", "", $t));
                        if (!empty($param) && $param !==$c && isset($us[$param]) && empty($us[$c]["referred_by"])) {
                            $us[$c]["referred_by"] = $param;
                            $us[$param]["refers"] = ($us[$param]["refers"] ?? 0) + 1;
                            sv("users.json", $us);
                            req("sendMessage", ["chat_id" => $param, "text" => "🔔 **অভিনন্দন!** আপনার রেফারে নতুন একজন মেম্বার যুক্ত হয়েছে।", "parse_mode" => "Markdown"]);
                        }

                        $us[$c]["state"] = "none";
                        $us[$c]["temp_uid"] = "";
                        sv("users.json", $us);
                        sendSubButtons($c);
                        continue;
                    }

                    if (mb_stripos($t, "বাতিল") !== false) {
                        $us[$c]["state"] = "none";
                        $us[$c]["temp_uid"] = "";
                        sv("users.json", $us);
                        req("sendMessage", ["chat_id" => $c, "text" => "❌ **বাতিল করা হয়েছে!** মূল মেনুতে ফিরে এসেছেন:", "parse_mode" => "Markdown", "reply_markup" => mainKb()]);
                        continue;
                    }

                    if (mb_stripos($t, "cancel") !== false) {
                        $us[$c]["state"] = "none";
                        $us[$c]["temp_uid"] = "";
                        sv("users.json", $us);
                        req("sendMessage", ["chat_id" => $c, "text" => "❌ **বাতিল করা হয়েছে!** মূল মেনুতে ফিরে এসেছেন:", "parse_mode" => "Markdown", "reply_markup" => mainKb()]);
                        continue;
                    }

                    if ((string)$c === (string)ADMIN_ID) {
                        if (strpos($t, "/approve") === 0) {
                            $parts = explode(" ", $t);
                            $target_uid =$parts[1] ?? "";
                            if (!empty($target_uid)) {
                                foreach ($ts as $sn =>$tk) {
                                    if ($tk["user_id"] === $target_uid &&$tk["status"] === "pending") {
                                        $ts[$sn]["status"] = "approved";
                                        if (isset($us[$target_uid])) {
                                            $us[$target_uid]["pending"] = max(0, $us[$target_uid]["pending"] - 1);
                                            $us[$target_uid]["approved"] += 1;
                                            $us[$target_uid]["balance"] += 4.00;
                                            $us[$target_uid]["processing"] = max(0, ($us[$target_uid]["processing"] ?? 0) - 4.00);

                                            $ref_by = $us[$target_uid]["referred_by"] ?? "";
                                            if (!empty($ref_by) && isset($us[$ref_by])) {
                                                $us[$ref_by]["balance"] += 0.40;
                                                req("sendMessage", ["chat_id" => $ref_by, "text" => "🎉 **রেফার কমিশন!** আপনার রেফারকৃত মেম্বারের কাজ এপ্রুভ হওয়ায় আপনি ৪.০ টাকা থেকে ১০% (০.৪০ টাকা) কমিশন পেয়েছেন।", "parse_mode" => "Markdown"]);
                                            }
                                        }
                                        sv("users.json", $us);
                                        sv("tasks.json", $ts);
                                        req("sendMessage", ["chat_id" => ADMIN_ID, "text" => "✅ কাজ এপ্রুভ হয়েছে এবং রেফার কমিশন সফলভাবে যুক্ত হয়েছে।"]);
                                        req("sendMessage", ["chat_id" => $target_uid, "text" => "🎉 **অভিনন্দন!** আপনার কাজ এপ্রুভ হয়েছে এবং ৪.০০ টাকা যোগ হয়েছে।", "parse_mode" => "Markdown"]);
                                        break;
                                    }
                                }
                            }
                            continue;
                        } elseif (strpos($t, "/reject") === 0) {
                            $parts = explode(" ", $t);
                            $target_uid =$parts[1] ?? "";
                            if (!empty($target_uid)) {
                                foreach ($ts as $sn =>$tk) {
                                    if ($tk["user_id"] === $target_uid &&$tk["status"] === "pending") {
                                        $ts[$sn]["status"] = "rejected";
                                        if (isset($us[$target_uid])) {
                                            $us[$target_uid]["pending"] = max(0, $us[$target_uid]["pending"] - 1);
                                            $us[$target_uid]["processing"] = max(0, ($us[$target_uid]["processing"] ?? 0) - 4.00);
                                        }
                                        sv("users.json", $us);
                                        sv("tasks.json", $ts);
                                        req("sendMessage", ["chat_id" => ADMIN_ID, "text" => "❌ কাজ বাতিল করা হয়েছে।"]);
                                        req("sendMessage", ["chat_id" => $target_uid, "text" => "❌ **দুঃখিত!** আপনার কাজটি বাতিল করা হয়েছে।", "parse_mode" => "Markdown"]);
                                        break;
                                    }
                                }
                            }
                            continue;
                        }
                    }

                    $st = $us[$c]["state"] ?? "none";

                    if ($st === "awaiting_uid") {
                        $us[$c]["temp_uid"] = $t;
                        $us[$c]["state"] = "awaiting_cookies";
                        sv("users.json", $us);
                        req("sendMessage", ["chat_id" => $c, "text" => "👉 **আপনার Facebook Cookies টাইপ করে পাঠান:**", "parse_mode" => "Markdown", "reply_markup" => cancelKb()]);
                        continue;
                    }

                    if ($st === "awaiting_cookies") {
                        $uid = $us[$c]["temp_uid"] ?? "N/A";
                        $cookies =$t;
                        $sn = count($ts) + 1;
                        $ts[$sn] = ["serial" => $sn, "user_id" => $c, "uid" => $uid, "cookies" => $cookies, "status" => "pending", "time" => date("Y-m-d H:i:s")];
                        sv("tasks.json", $ts);

                        $us[$c]["pending"] += 1;
                        $us[$c]["processing"] = ($us[$c]["processing"] ?? 0) + 4.00;
                        $us[$c]["state"] = "none";
                        $us[$c]["temp_uid"] = "";
                        sv("users.json", $us);

                        $admin_msg = "🔔 **নতুন টাস্ক সাবমিট হয়েছে!**\n\n🆔 ইউজার: `" . $c . "`\n🔢 সিরিয়াল: #" . $sn . "\n📌 UID: `" . $uid . "`\n🍪 Cookies: `" . $cookies . "`\n\n🟢 `/approve " . $c . "`\n🔴 `/reject " . $c . "`";
                        req("sendMessage", ["chat_id" => ADMIN_ID, "text" => $admin_msg, "parse_mode" => "Markdown"]);
                        req("sendMessage", ["chat_id" => $c, "text" => "✅ **আপনার কাজ সফলভাবে জমা হয়েছে!** (সিরিয়াল নং: #" . $sn . ")", "parse_mode" => "Markdown", "reply_markup" => mainKb()]);
                        continue;
                    }

                    if (mb_stripos($t, "প্রোফাইল") !== false) {
                        $u =$us[$c];$msg = "📊 **প্রোফাইল**\n\n🆔 আইডি: `" . $c . "`\n💵 ব্যালেন্স: " . number_format($u["balance"], 2) . " BDT\n⏳ প্রসেসিং: " . number_format($u["processing"], 2) . " BDT\n⏱️ পেন্ডিং: " . $u["pending"] . " টি\n✅ এপ্রুভড: " . $u["approved"] . " টি\n👥 রেফার: " . $u["refers"] . " জন";
                        req("sendMessage", ["chat_id" => $c, "text" => $msg, "parse_mode" => "Markdown"]);
                    } elseif (mb_stripos($t, "উত্তোলন") !== false) {
                        $u = $us[$c];
                        if ($u["balance"] < 50) {
                            req("sendMessage", ["chat_id" => $c, "text" => "❌ মিনিমাম উত্তোলন ৫০.০০ টাকা। বর্তমান ব্যালেন্স: " . number_format($u["balance"], 2) . " টাকা।", "parse_mode" => "Markdown"]);
                        } else {
                            req("sendMessage", ["chat_id" => $c, "text" => "💳 **উত্তোলন মাধ্যম সিলেক্ট করুন:**", "parse_mode" => "Markdown", "reply_markup" => ["keyboard" => [[["text" => "📱 Bkash"], ["text" => "📱 Nagad"]], [["text" => "❌ বাতিল (Cancel)"]]], "resize_keyboard" => true]]);
                        }
                    } elseif (mb_stripos($t, "রেফার") !== false) {
                        $ref_link = "https://t.me/fbsell2bot?start=" . $c;
                        $ref_msg = "আপনার রেফার লিংক দিয়ে কোনো মেম্বার কাজ এপ্রুভ করালে আপনি ১০% কমিশন (০.৪০ টাকা) পাবেন:\n\n`" . $ref_link . "`";
                        req("sendMessage", ["chat_id" => $c, "text" => $ref_msg, "parse_mode" => "Markdown"]);
                    } elseif (mb_stripos($t, "সাপোর্ট") !== false) {
                        req("sendMessage", ["chat_id" => $c, "text" => "👨‍💻 যোগাযোগ: @sumon2222121", "parse_mode" => "Markdown"]);
                    } elseif (mb_stripos($t, "কাজ") !== false) {
                        req("sendMessage", ["chat_id" => $c, "text" => "📌 **প্ল্যাটফর্ম সিলেক্ট করুন:**", "parse_mode" => "Markdown", "reply_markup" => ["keyboard" => [[["text" => "🔵 Facebook Tasks"]], [["text" => "❌ বাতিল (Cancel)"]]], "resize_keyboard" => true]]);
                    } elseif (mb_stripos($t, "Tasks") !== false) {
                        req("sendMessage", ["chat_id" => $c, "text" => "📌 **প্ল্যাটফর্ম সিলেক্ট করুন:**", "parse_mode" => "Markdown", "reply_markup" => ["keyboard" => [[["text" => "🔵 Facebook Tasks"]], [["text" => "❌ বাতিল (Cancel)"]]], "resize_keyboard" => true]]);
                    } elseif (mb_stripos($t, "Facebook") !== false) {
                        req("sendMessage", ["chat_id" => $c, "text" => "⚡ **কাজের ধরণ সিলেক্ট করুন:**", "parse_mode" => "Markdown", "reply_markup" => ["keyboard" => [[["text" => "🍪 Fb Cookies (4.00 BDT)"]], [["text" => "❌ বাতিল (Cancel)"]]], "resize_keyboard" => true]]);
                    } elseif (mb_stripos($t, "Cookies") !== false) {
                        $fn = ["Thomas", "James", "John", "Emma"];
                        $ln = ["Smith", "Johnson", "Williams"];
                        $f =$fn[array_rand($fn)];$l = $ln[array_rand($ln)];
                        $pass =$cfg["password"] ?? "Sumon@21";
                        $msg = "⚡ **অ্যাকাউন্ট বিবরণী:**\n\n▫️ First Name: `" . $f . "`\n▫️ Last Name: `" . $l . "`\n▫️ Password: `" . $pass . "`\n\nকাজ শেষে নিচের বাটনে ক্লিক করুন:";
                        req("sendMessage", ["chat_id" => $c, "text" => $msg, "parse_mode" => "Markdown", "reply_markup" => ["keyboard" => [[["text" => "🆔 Submit UID"]], [["text" => "❌ বাতিল (Cancel)"]]], "resize_keyboard" => true]]);
                    } elseif (mb_stripos($t, "Submit") !== false) {
                        $us[$c]["state"] = "awaiting_uid";
                        sv("users.json", $us);
                        req("sendMessage", ["chat_id" => $c, "text" => "👉 **আপনার Facebook UID টাইপ করে পাঠান:**", "parse_mode" => "Markdown", "reply_markup" => cancelKb()]);
                    } elseif (mb_stripos($t, "UID") !== false) {
                        $us[$c]["state"] = "awaiting_uid";
                        sv("users.json", $us);
                        req("sendMessage", ["chat_id" => $c, "text" => "👉 **আপনার Facebook UID টাইপ করে পাঠান:**", "parse_mode" => "Markdown", "reply_markup" => cancelKb()]);
                    } else {
                        req("sendMessage", ["chat_id" => $c, "text" => "নিচে থেকে অপশন সিলেক্ট করুন:", "reply_markup" => mainKb()]);
                    }
                }
            }
        }
    } catch (Exception $e) {}
    usleep(50000);
}
