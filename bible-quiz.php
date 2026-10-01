<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$questionBank = [
 ['question'=>'Who built the ark?','options'=>['Moses','Noah','Abraham','David'],'answer'=>'Noah'],
 ['question'=>'What is the first book of the Bible?','options'=>['Exodus','Matthew','Genesis','Psalms'],'answer'=>'Genesis'],
 ['question'=>'Who led the Israelites out of Egypt?','options'=>['Joshua','Moses','Aaron','Samuel'],'answer'=>'Moses'],
 ['question'=>'How many disciples did Jesus choose?','options'=>['10','12','14','7'],'answer'=>'12'],
 ['question'=>'Where was Jesus born?','options'=>['Nazareth','Jerusalem','Bethlehem','Capernaum'],'answer'=>'Bethlehem'],
 ['question'=>'Who defeated Goliath?','options'=>['David','Solomon','Saul','Jonathan'],'answer'=>'David'],
 ['question'=>'What was Jesus\' first miracle?','options'=>['Healing a blind man','Walking on water','Turning water into wine','Feeding five thousand'],'answer'=>'Turning water into wine'],
 ['question'=>'Which Psalm begins with “The Lord is my shepherd”?','options'=>['Psalm 1','Psalm 23','Psalm 91','Psalm 119'],'answer'=>'Psalm 23'],
 ['question'=>'Who was known for extraordinary wisdom?','options'=>['Solomon','Samson','Elijah','Peter'],'answer'=>'Solomon'],
 ['question'=>'What is the last book of the Bible?','options'=>['Jude','Acts','Revelation','Romans'],'answer'=>'Revelation'],
];
$seed = (int) date('Ymd');
usort($questionBank, static fn($a,$b) => crc32($a['question'].$seed) <=> crc32($b['question'].$seed));
$dailyQuiz = array_slice($questionBank, 0, 10);
$pdo = initializeDatabase();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $date = date('Y-m-d');
  $exists = $pdo->prepare('SELECT id FROM bible_quiz_attempts WHERE user_id = ? AND quiz_date = ?');
  $exists->execute([$_SESSION['user_id'], $date]);
  if ($exists->fetchColumn()) { $message = 'You have already completed today’s quiz. Come back tomorrow for ten new questions.'; }
  else {
    $answers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
    $safe = []; $score = 0;
    foreach ($dailyQuiz as $index => $question) {
      $answer = (string) ($answers[$index] ?? '');
      $safe[$index] = in_array($answer, $question['options'], true) ? $answer : '';
      if ($safe[$index] === $question['answer']) $score++;
    }
    $save = $pdo->prepare('INSERT INTO bible_quiz_attempts (user_id, quiz_date, score, total_questions, answers) VALUES (?, ?, ?, ?, ?)');
    $save->execute([$_SESSION['user_id'], $date, $score, count($dailyQuiz), json_encode($safe)]);
    $message = 'Quiz complete: '.$score.'/'.count($dailyQuiz).'.';
  }
}
$attempt = $pdo->prepare('SELECT score, total_questions, answers FROM bible_quiz_attempts WHERE user_id = ? AND quiz_date = ?');
$attempt->execute([$_SESSION['user_id'], date('Y-m-d')]);
$todayAttempt = $attempt->fetch();
$missed = [];
if ($todayAttempt) {
  $answers = json_decode($todayAttempt['answers'] ?? '{}', true) ?: [];
  foreach ($dailyQuiz as $i => $question) if (($answers[$i] ?? '') !== $question['answer']) $missed[] = ['question'=>$question['question'], 'selected'=>$answers[$i] ?? '', 'correct'=>$question['answer']];
}
$stats = $pdo->prepare('SELECT COUNT(*) AS quiz_count, COALESCE(AVG(score / total_questions * 100),0) AS average_score, COALESCE(MAX(score),0) AS best_score FROM bible_quiz_attempts WHERE user_id = ?');
$stats->execute([$_SESSION['user_id']]); $stats = $stats->fetch();
?>
<!doctype html>
<html lang="en"><head><link rel="icon" type="image/png" href="assets/mglg-favicon.png"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Bible Quiz | MGLG</title><link rel="stylesheet" href="theme.css"><link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav"><style>.quiz-form{display:grid;gap:14px;max-width:760px}.quiz-question,.quiz-review{margin:0;padding:20px;border:1px solid var(--line);background:var(--card)}.quiz-question legend{color:var(--ink);font-weight:bold}.quiz-options{display:grid;gap:8px;margin-top:14px}.quiz-option{display:flex;gap:9px;color:var(--muted)}.quiz-option input{accent-color:var(--green)}.quiz-submit{border:0;border-radius:8px;padding:14px;background:var(--brown);color:#fff;font-weight:800;cursor:pointer}.quiz-result{max-width:760px;margin:24px 0;padding:20px;border-left:4px solid var(--green);background:var(--mint);color:var(--green)}.quiz-review{max-width:760px;margin:24px 0}.quiz-review h2{margin:0 0 16px}.review-item{padding:14px 0;border-top:1px solid var(--line)}.review-item:first-of-type{border:0}.wrong{color:#9b382d}.right{color:var(--green)}.quiz-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:30px;max-width:760px}.quiz-stat{padding:18px;border:1px solid var(--line);background:var(--card)}.quiz-stat strong{display:block;margin-top:6px;color:var(--brown);font-size:1.5rem}@media(max-width:700px){.quiz-stats{grid-template-columns:1fr}}</style></head>
<body>
<div class="page-shell"><header class="page-header"><a class="brand" href="dashboard.php">My Generation Loves God</a><?php $activeNav='quiz'; include 'site-nav.php'; ?></header><main class="page-main"><div class="eyebrow eyebrow--xs">Grow in faith</div><h1 class="page-title--xs">Daily Bible quiz.</h1><p class="lead lead--xs">Test your knowledge, learn something new and keep growing in the Word with the MGLG community.</p><?php if ($message) : ?><div class="quiz-result"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if (!$todayAttempt) : ?><form class="quiz-form" method="post"><?php foreach ($dailyQuiz as $index=>$question) : ?><fieldset class="quiz-question"><legend><?php echo ($index+1).'. '.htmlspecialchars($question['question']); ?></legend><div class="quiz-options"><?php foreach ($question['options'] as $option) : ?><label class="quiz-option"><input type="radio" name="answers[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($option); ?>" required><?php echo htmlspecialchars($option); ?></label><?php endforeach; ?></div></fieldset><?php endforeach; ?><button class="quiz-submit" type="submit">Submit today’s quiz</button></form><?php elseif ($missed) : ?><section class="quiz-review"><h2>Review your missed questions</h2><?php foreach ($missed as $index=>$item) : ?><article class="review-item"><strong><?php echo ($index+1).'. '.htmlspecialchars($item['question']); ?></strong><p class="wrong">Your answer: <?php echo htmlspecialchars($item['selected'] ?: 'No answer selected'); ?></p><p class="right">Correct answer: <?php echo htmlspecialchars($item['correct']); ?></p></article><?php endforeach; ?></section><?php endif; ?><div class="quiz-stats"><div class="quiz-stat">Average score<strong><?php echo number_format((float)$stats['average_score'],1); ?>%</strong></div><div class="quiz-stat">Best score<strong><?php echo (int)$stats['best_score']; ?>/10</strong></div><div class="quiz-stat">Quizzes completed<strong><?php echo (int)$stats['quiz_count']; ?></strong></div></div></main><?php include 'site-footer.php'; ?></div><?php include 'site-cross-background.php'; ?><script src="theme.js?v=20260929-light-only"></script></body></html>
