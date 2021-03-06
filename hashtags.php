<?php require($_SERVER['DOCUMENT_ROOT'] . "/static/config.inc.php"); ?>
<?php require($_SERVER['DOCUMENT_ROOT'] . "/static/conn.php"); ?>
<?php require($_SERVER['DOCUMENT_ROOT'] . "/lib/profile.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <link href="/static/css/required.css" rel="stylesheet">
    <title>Witter: What are you doing?</title>
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
    <?php $user = getUserFromName($_SESSION['siteusername'], $conn); ?>
    <script>function onLogin(token){ document.getElementById('submitform').submit(); }</script>
</head>
<body id="front">
<div id="container">
    <?php require($_SERVER['DOCUMENT_ROOT'] . "/static/header.php");
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        if(!isset($_SESSION['siteusername'])){ $error = "you are not logged in"; goto skipcomment; }
        if(!$_POST['comment']){ $error = "your comment cannot be blank"; goto skipcomment; }
        if(strlen($_POST['comment']) > 500){ $error = "your comment must be shorter than 500 characters"; goto skipcomment; }
        if(!isset($_POST['g-recaptcha-response'])){ $error = "captcha validation failed"; goto skipcomment; }
        if(!validateCaptcha($config['recaptcha_secret'], $_POST['g-recaptcha-response'])) { $error = "captcha validation failed"; goto skipcomment; }

        $stmt = $conn->prepare("INSERT INTO `weets` (realid, author, contents) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $uniqid, $_SESSION['siteusername'], $text);
        $uniqid = time() . uniqid();
        $text = htmlspecialchars($_POST['comment']);
        $stmt->execute();
        $stmt->close();
        skipcomment:
    }
    ?>
    <div id="content">
        <?php if(!isset($_SESSION['siteusername'])) { ?>
            <div style="background-color: lightyellow;" class="wrapper">
                <big><big><big>Hey there! You arent logged in!</big></big></big><br>
                <img style="float: left; margin-right: 5px;" src="/static/girl.gif">Witter is a free service that lets you keep in touch with people through the exchange of quick, frequent answers to one simple question: What are you doing? Log in or register to post.
            </div><br><br><br><br><br><br>
        <?php } ?>
        <div class="wrapper">
            <?php if(isset($_SESSION['siteusername'])) { ?>
                <div class="customtopRight">
                    <img id="pfp" style="vertical-align: middle;" src="/dynamic/pfp/<?php echo $user['pfp']; ?>"> <b><big><big><?php echo $_SESSION['siteusername']; ?></big></big></b><br>
                    <table id="cols">
                        <tr>
                            <th style="width: 33%;">&nbsp;</th>
                            <th style="width: 33%;">&nbsp;</th>
                            <th style="width: 33%;">&nbsp;</th>
                        </tr>
                        <tr>
                            <td><big><big><big><b><?php echo getFollowing($_SESSION['siteusername'], $conn); ?></b></big></big></big><br><span id="blue">following</span></td>
                            <td><big><big><big><b><?php echo getFollowers($_SESSION['siteusername'], $conn); ?></b></big></big></big><br><span id="blue">followers</span></td>
                            <td><big><big><big><b><?php echo getWeets(rhandleTag($_SESSION['siteusername']), $conn); ?></b></big></big></big><br><span id="blue">weets</span></td>
                        </tr>
                    </table><br>
                    <?php require($_SERVER['DOCUMENT_ROOT'] . "/static/followRequire.php"); ?>
                    <div class="altbg">
                        <a href="/home.php">Home</a><br>
                        <a href="/pms.php">Private Messages [200]</a>
                    </div><br>
                    <div class="altbg">
                        <center><a href="https://discord.gg/J5ZDsak">Join the Discord server</a></center>
                    </div><br>
                </div>
            <?php } ?>
            <div class="customtopLeft">
                <big><big><big>#<?php echo htmlspecialchars($_GET['n']); ?></big></big></big>
                <script src='/js/limit.js'></script><br>
                <table id="feed">
                    <tr>
                        <th style="width: 48px;">&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                    <?php
                    ini_set('display_errors', 1);
                    ini_set('display_startup_errors', 1);
                    error_reporting(E_ALL);

                    $stmt = $conn->prepare("SELECT COUNT(*) FROM weets WHERE contents LIKE ?");
                    $like = "%" . $_GET['n'] . "%";
                    $stmt->bind_param('s', $like);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $total_pages = $result->num_rows;
                    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
                    $num_results_on_page = 16;

                    $stmt = $conn->prepare("SELECT * FROM weets WHERE contents LIKE ? ORDER BY id DESC LIMIT ?,?");
                    $calc_page = ($page - 1) * $num_results_on_page;
                    $like = "%" . $_GET['n'] . "%";
                    $stmt->bind_param('sii', $like, $calc_page, $num_results_on_page);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <big><big><big>
                                <td>
                                    <img id="pfp" src="/dynamic/pfp/<?php echo getPFPFromUser($row['author'], $conn); ?>">
                                </td>
                                <td><a id="tag" href="/u.php?n=<?php echo handleTag($row['author']); ?>"><?php echo($row['author']); ?></a>
                                    <?php if(returnVerifiedFromUsername($row['author'], $conn) != "") { ?> <span style="border-radius: 10px; background-color: deepskyblue; color: white; padding: 3px;"><?php echo(returnVerifiedFromUsername($row['author'], $conn)); ?></span> <?php } ?>
                                    <div id="floatRight" class="dropdown">
                                        <span><img style="vertical-align: middle;" src="/static/witter-dotdotdot.png"></span>
                                        <div class="dropdown-content">
                                            <a href="#<?php //echo report.php?r=$row['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-report.png"></a><br>
                                            <?php if(isset($_SESSION['siteusername']) && $row['author'] == $_SESSION['siteusername']) { ?>
                                                <a href="/delete.php?rid=<?php echo $row['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-trash.png"></a><br>
                                                <a href="/edit.php?rid=<?php echo $row['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-edit.png"></a><br>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <span id="floatRight">
                                    <?php if(ifLiked($_SESSION['siteusername'], $row['id'], $conn) == true) { ?>
                                        <a href="/unlike.php?id=<?php echo $row['id']; ?>"><img style="vertical-align: middle;" src="/static/witter-like.png"></a>
                                    <?php } else { ?>
                                        <a href="/like.php?id=<?php echo $row['id']; ?>"><img style="vertical-align: middle;" src="/static/witter-liked.png"></a>
                                    <?php } ?>
                                </span>
                                    <div id="feedtext"><?php echo parseText($row['contents']); ?> </div>
                                    <small id="grey">about <?php echo time_elapsed_string($row['date']); ?> from web
                                        <span id="floatRight">
                                        <?php echo getComments($row['realid'], $conn); ?><img style="vertical-align: middle;" src="/static/witter-replies.png"> &bull; <a href="/v.php?rid=<?php echo $row['realid']; ?>">Reply</a> &bull; <a href="/home.php?text=https://witter.spacemy.xyz/embed/?i=<?php echo $row['realid']; ?>">Reweet</a>
                                    </span>
                                    </small><br>
                                    <?php
                                    $likes = getLikesReal($row['id'], $conn);
                                    while($row = $likes->fetch_assoc()) {
                                        ?>
                                        <a href="/u.php?n=<?php echo handleTag($row['fromu']); ?>"><img style="width: 30px; height: 30px; margin-left: 2px;" id="pfp" src="/dynamic/pfp/<?php echo getPFPFromUser($row['fromu'], $conn); ?>"></a>&nbsp;
                                    <?php } ?>
                                </td>
                            </big></big></big>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <center>
                    <?php if (ceil($total_pages / $num_results_on_page) > 0): ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1 ?>&n=<?php echo $_GET['n']; ?>">Prev</a>
                        <?php endif; ?>

                        <?php if ($page > 3): ?>
                            <a href="?page=1&n=<?php echo $_GET['n']; ?>">1</a>
                            ...
                        <?php endif; ?>

                        <?php if ($page-2 > 0): ?><a href="?page=<?php echo $page-2 ?>&n=<?php echo $_GET['n']; ?>"><?php echo $page-2 ?></a><?php endif; ?>
                        <?php if ($page-1 > 0): ?><a href="?page=<?php echo $page-1 ?>&n=<?php echo $_GET['n']; ?>"><?php echo $page-1 ?></a><?php endif; ?>

                        <a href="?page=<?php echo $page ?>&n=<?php echo $_GET['n']; ?>"><?php echo $page ?></a>

                        <?php if ($page+1 < ceil($total_pages / $num_results_on_page)+1): ?><a href="?page=<?php echo $page+1 ?>&n=<?php echo $_GET['n']; ?>"><?php echo $page+1 ?></a></li><?php endif; ?>
                        <?php if ($page+2 < ceil($total_pages / $num_results_on_page)+1): ?><a href="?page=<?php echo $page+2 ?>&n=<?php echo $_GET['n']; ?>"><?php echo $page+2 ?></a></li><?php endif; ?>

                        <?php if ($page < ceil($total_pages / $num_results_on_page)-2): ?>
                            ...
                            <a href="?page=<?php echo ceil($total_pages / $num_results_on_page) ?>&n=<?php echo $_GET['n']; ?>"><?php echo ceil($total_pages / $num_results_on_page) ?></a>
                        <?php endif; ?>

                        <?php if ($page < ceil($total_pages / $num_results_on_page)): ?>
                            <a href="?page=<?php echo $page+1 ?>&n=<?php echo $_GET['n']; ?>">Next</a>
                        <?php endif; ?>
                    <?php endif; ?>
            </div>
            </center>
            <?php require($_SERVER['DOCUMENT_ROOT'] . "/static/footer.php"); ?>
        </div>
    </div>
</div>
</body>
</html>