<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $input_pw = $_POST['password'];

    // IDから決定的なパスワードを生成
    function generatePassword($id) {
        return substr(hash('sha256', 'secret_salt' . $id), 0, 8);
    }

    $correct_pw = generatePassword($id);

    // 認証チェック
    if ($input_pw === $correct_pw) {
        $_SESSION['user_id'] = $id;  // セッション発行
        header("Location: members_only.php"); // 会員エリアへリダイレクト
        exit;
    } else {
        echo "ログイン失敗: IDまたはパスワードが間違っています。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
</head>
<body>
    <h2>ログイン</h2>
    <form method="post">
        <label>ID: <input type="text" name="id" required></label><br>
        <label>パスワード: <input type="password" name="password" required></label><br>
        <button type="submit">ログイン</button>
    </form>
</body>
</html>
