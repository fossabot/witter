<?php require($_SERVER['DOCUMENT_ROOT'] . "/static/config.inc.php"); ?>
<?php require($_SERVER['DOCUMENT_ROOT'] . "/static/conn.php"); ?>
<?php require($_SERVER['DOCUMENT_ROOT'] . "/lib/profile.php");
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="/static/css/required.css" rel="stylesheet">
    <title>Witter: What are you doing?</title>
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
    <?php $user = getUserFromName(rhandleTag($_GET['n']), $conn); ?>
    <meta property="og:title" content="@<?php echo $user['username']; ?>" />
    <meta property="og:description"
          content="<?php echo $user['bio']; ?>" />
    <meta property="og:image" content="https://witter.spacemy.xyz/dynamic/pfp/<?php echo $user['pfp']; ?>" />
    <script>function onLogin(token){ document.getElementById('submitform').submit(); }</script>
</head>
<body id="front">
<div id="container">
    <?php require($_SERVER['DOCUMENT_ROOT'] . "/static/header.php");
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ?>
    <div id="content">
        <?php if(!isset($_SESSION['siteusername'])) { ?>
            <div style="background-color: lightyellow;" class="wrapper">
                <big><big><big>Hey there! <b><?php echo $user['username']; ?></b> is using Witter.</big></big></big><br>
                <img style="float: left; margin-right: 5px;" src="/static/girl.gif">Witter is a free service that lets you keep in touch with people through the exchange of quick, frequent answers to one simple question: What are you doing? Join today to start recieving <?php echo $user['username']; ?>'s updates.
            </div><br><br><br><br><br><br>
        <?php } ?>
        <div class="wrapper">
            <?php if($user['banstatus'] == "suspended") { ?>
                <br>
                <div style='padding: 5px; border: 5px solid green;'>
                    <h4 id='noMargin'>
                        This user has been suspended.
                    </h4>
                </div>
                <?php die(); ?>
            <?php } ?>
            <?php if(!isset($user['banstatus'])) { ?>
                <br>
                <div style='padding: 5px; border: 5px solid green;'>
                    <h4 id='noMargin'>
                        This user does not exist or has been permanately deleted.
                    </h4>
                </div>
                <?php die(); ?>
            <?php } ?>
            <div class="customtopRight">
                Name: <b><big><?php echo $user['username']; ?></big></b><br>
                <table id="cols">
                    <tr>
                        <th style="width: 33%;">&nbsp;</th>
                        <th style="width: 33%;">&nbsp;</th>
                        <th style="width: 33%;">&nbsp;</th>
                    </tr>
                    <tr>
                        <td><big><big><big><b><?php echo getFollowing($user['username'], $conn); ?></b></big></big></big><br><span id="blue">following</span></td>
                        <td><big><big><big><b><?php echo getFollowers($user['username'], $conn); ?></b></big></big></big><br><span id="blue">followers</span></td>
                        <td> </td>
                    </tr>
                </table><br>

                <div class="altbg">
                    <b>Tweets</b><span id="floatRight"><?php echo getWeets(rhandleTag($_GET['n']), $conn); ?></span>
                </div>
                <span id="blue"><a style="text-decoration: none; padding-left: 5px;color: #6d94c8;" href="/favorites.php?n=<?php echo $user['username']; ?>">Favorites</a></span>
                <br><br>
                <div class="altbg">
                    <span id="blue">Followers</span><br>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM follow WHERE reciever = ?");
                    $stmt->bind_param("s", $user['username']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while($row = $result->fetch_assoc()) {
                        ?>
                        <a href="/u.php?n=<?php echo handleTag($row['sender']); ?>"><img style="width: 30px; height: 30px;" src="/dynamic/pfp/<?php echo getPFPFromUser($row['sender'], $conn); ?>"></a>
                        <?php
                    }
                    $stmt->close();
                    ?>
                </div><br>
                <div class="altbg">
                    <span id="blue">Bio</span>
                    <?php echo $user['bio']; ?>
                </div>
            </div>
            <div class="customtopLeft">
                <img id="pfp" style="height: 13%; width: 13%;" src="/dynamic/pfp/<?php echo $user['pfp']; ?>"><h1 style="margin-left: 80px;"><?php echo $user['username']; ?></h1><br>
                <?php if(isset($_SESSION['errorMsg'])) { echo "<div style='padding: 5px; border: 5px solid green;'><h4 id='noMargin'>" . $_SESSION['errorMsg']; unset($_SESSION['errorMsg']); echo "</h4></div><br>"; }?>
                <?php
                if(ifFollowing(rhandleTag($_GET['n']), @$_SESSION['siteusername'], $conn) == false) {?>
                    <a href="/follow.php?n=<?php echo $user['username']; ?>"><button>Follow</button></a>
                <?php } else { ?>
                    <a href="/unfollow.php?n=<?php echo $user['username']; ?>"><button>Unfollow</button></a>
                <?php }
                ?>
                <table id="feed">
                    <tr>
                        <th style="width: 48px;">&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM likes WHERE fromu = ?");
                    $stmt->bind_param("s", $tag);
                    $tag = rhandleTag($_GET['n']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) echo('This user has no favorites.');
                    while($row = $result->fetch_assoc()) {
                        $weetBuffer = getWeetFromID($row['torid'], $conn);
                        while($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                            <big><big><big>
                                <td>
                                    <img id="pfp" src="/dynamic/pfp/<?php echo getPFPFromUser($weetBuffer['author'], $conn); ?>">
                                </td>
                                <td><a href="/u.php?n=<?php echo handleTag($weetBuffer['author']); ?>"><?php echo($weetBuffer['author']); ?></a>
                                    <?php if(returnVerifiedFromUsername($weetBuffer['author'], $conn) != "") { ?> <span style="border-radius: 10px; background-color: deepskyblue; color: white; padding: 3px;"><?php echo(returnVerifiedFromUsername($weetBuffer['author'], $conn)); ?></span> <?php } ?>
                                    <div id="floatRight" class="dropdown">
                                        <span><img style="vertical-align: middle;" src="/static/witter-dotdotdot.png"></span>
                                        <div class="dropdown-content">
                                            <a href="#<?php $//echo report.php?r=$weetBuffer['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-report.png"></a><br>
                                            <?php if(isset($_SESSION['siteusername']) && $weetBuffer['author'] == $_SESSION['siteusername']) { ?>
                                                <a href="/delete.php?rid=<?php echo $weetBuffer['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-trash.png"></a><br>
                                                <a href="/edit.php?rid=<?php echo $weetBuffer['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-edit.png"></a><br>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div id="feedtext"><?php echo parseText($weetBuffer['contents']); ?> </div>
                                    <small><?php echo time_elapsed_string($weetBuffer['date']); ?> from web
                                        <?php if(ifLiked($_SESSION['siteusername'], $weetBuffer['id'], $conn) == true) { ?>
                                            <a href="/unlike.php?id=<?php echo $weetBuffer['id']; ?>"><img style="vertical-align: middle;" src="/static/witter-like.png">Unlike</a>
                                        <?php } else { ?>
                                            <a href="/like.php?id=<?php echo $weetBuffer['id']; ?>"><img style="vertical-align: middle;" src="/static/witter-liked.png">Like</a>
                                        <?php } ?>
                                        <a href="/v.php?rid=<?php echo $weetBuffer['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-reply.png">Reply</a>
                                        <?php echo getComments($weetBuffer['realid'], $conn); ?><img style="vertical-align: middle;" src="/static/witter-replies.png">
                                        <a href="/home.php?text=https://witter.spacemy.xyz/embed/?i=<?php echo $weetBuffer['realid']; ?>"><img style="vertical-align: middle;" src="/static/witter-reweet.png">Reweet</a>
                                    </small><br>
                                    <?php
                                    $likes = getLikesReal($weetBuffer['id'], $conn);
                                    while($weetBuffer = $likes->fetch_assoc()) {
                                        ?>
                                        <a href="/u.php?n=<?php echo handleTag($weetBuffer['fromu']); ?>"><img style="width: 30px; height: 30px; margin-left: 2px;" id="pfp" src="/dynamic/pfp/<?php echo getPFPFromUser($weetBuffer['fromu'], $conn); ?>"></a>&nbsp;
                                    <?php } ?>
                                </td>
                            </big></big></big>
                        </tr>
                        <?php
                    }
                    $stmt->close();
                    ?>
                </table>
            </div>
            <?php require($_SERVER['DOCUMENT_ROOT'] . "/static/footer.php"); ?>
        </div>
    </div>
</div>
</body>
</html>