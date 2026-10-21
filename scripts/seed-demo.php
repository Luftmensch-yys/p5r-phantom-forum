<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../conn.php';
mysqli_begin_transaction($conn);
$names = ['P5R Demo','雨夜电车','卢布朗常客','放学后','面具收藏家','猫猫观察员','涩谷散步'];
$ids = [];
foreach ($names as $i => $name) {
    $email = $i === 0 ? 'demo@p5r.local' : 'sample'.$i.'@example.invalid';
    $hash = password_hash('P5rDemo123', PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'INSERT INTO users (username,email,password) VALUES (?,?,?) ON DUPLICATE KEY UPDATE username=VALUES(username)');
    mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash); mysqli_stmt_execute($stmt);
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email=?');
    mysqli_stmt_bind_param($stmt,'s',$email); mysqli_stmt_execute($stmt);
    $ids[] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['id'];
}
$count = mysqli_query($conn, "SELECT COUNT(*) AS n FROM messages WHERE content LIKE '【演示】%'");
if ((int)mysqli_fetch_assoc($count)['n'] === 0) {
    $posts = [
      [1,'【演示】你们相信怪盗团存在吗？如果能发一封预告信，你最想改变什么？',-1],
      [2,'【演示】我投存在！比起超能力，更喜欢大家愿意为彼此站出来的勇气。',0],
      [0,'【演示】希望每个被忽略的声音都能被听见。这也是我做这个留言空间的原因。',0],
      [3,'【演示】下雨天、咖啡和背景音乐，这个氛围太适合慢慢聊天了。',-1],
      [4,'【演示】红黑配色和撕纸边缘很有感觉，投票条也会跟着选择变化。',-1],
      [5,'【演示】可以改投票吗？刚刚点了不存在，现在想加入另一边。',-1],
      [0,'【演示】可以，重新选择就会更新，每个账号只保留一票。',5],
      [6,'【演示】今天第一次来，先留个脚印。期待在这里遇到更多同好！',-1],
    ];
    $messageIds=[];
    foreach($posts as $i=>$post) {
        $uid=$ids[$post[0]]; $name=$names[$post[0]]; $content=$post[1];
        $reply=$post[2] < 0 ? 0 : $messageIds[$post[2]];
        $date=date('Y-m-d H:i:s',time()-(count($posts)-$i)*1200);
        $stmt=mysqli_prepare($conn,'INSERT INTO messages (uid,username,content,reply_id,create_time) VALUES (?,?,?,?,?)');
        mysqli_stmt_bind_param($stmt,'issis',$uid,$name,$content,$reply,$date);mysqli_stmt_execute($stmt);
        $messageIds[]=(int)mysqli_insert_id($conn);
    }
}
foreach($ids as $i=>$uid) {
    $vote=$i < 5 ? '存在' : '不存在';
    $stmt=mysqli_prepare($conn,'INSERT INTO votes (user_id,vote_option) VALUES (?,?) ON DUPLICATE KEY UPDATE vote_option=VALUES(vote_option)');
    mysqli_stmt_bind_param($stmt,'is',$uid,$vote);mysqli_stmt_execute($stmt);
}
mysqli_commit($conn);
echo "Demo users, messages, replies and votes ready.\n";
