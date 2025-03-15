<?php

session_start(); // 启动会话

// 检查用户是否已登录
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    // 如果没有登录，重定向到登录页面
    header('Location: DatabaseLogin.php');
    exit();
}
require_once __DIR__ . '/vendor/autoload.php';
require_once 'src/access.php'; // Include WeChat classes

// Check if CommonMarkConverter is available
if (!class_exists('League\CommonMark\CommonMarkConverter')) {
    die("Error: 'league/commonmark' package is not installed. Run 'composer require league/commonmark' in your project directory.");
}

use League\CommonMark\CommonMarkConverter;

// Initialize WeChat classes
$weixinAccess = new Access();
$material = new Material();
$draft = new Draft();

function markdownToHtml($markdown) {
    $converter = new CommonMarkConverter();
    return $converter->convert($markdown)->getContent();
}

// Function to convert Markdown table syntax within <p> tags to HTML table
function fixMarkdownTableInHtml($html) {
    $pattern = '/<p>\s*\|[^<]*\|.*?(?:<\/p>|\n\s*\|[^<]*\|.*?)*<\/p>/s';
    if (preg_match($pattern, $html, $matches)) {
        $markdownTable = $matches[0];
        $markdownTable = str_replace(['<p>', '</p>'], '', $markdownTable);
        $lines = explode("\n", trim($markdownTable));
        $tableHtml = "<table>\n<thead>\n<tr>";
        
        $headers = array_map('trim', explode('|', trim($lines[0], '|')));
        foreach ($headers as $header) {
            $header = str_replace(['<strong>', '</strong>'], '', $header);
            $tableHtml .= "<th>" . htmlspecialchars(trim($header)) . "</th>";
        }
        $tableHtml .= "</tr>\n</thead>\n<tbody>\n";

        for ($i = 2; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') continue;
            $row = array_map('trim', explode('|', trim($lines[$i], '|')));
            $tableHtml .= "<tr>";
            foreach ($row as $cell) {
                $cell = preg_replace('/<strong>(.*?)<\/strong>/', '$1', $cell);
                $tableHtml .= "<td>" . htmlspecialchars(trim($cell)) . "</td>";
            }
            $tableHtml .= "</tr>\n";
        }
        $tableHtml .= "</tbody>\n</table>";

        $html = preg_replace($pattern, $tableHtml, $html);
    }
    return $html;
}

// Process form submission and generate JSON
$jsonOutput = '';
$convertedContents = [];
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$uploadedImageUrl = ''; // Store the uploaded image media_id

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articles = [];
    $articleCount = (int)($_POST['article_count'] ?? 1);

    // Upload Image
    if (isset($_POST['upload_img']) && isset($_FILES['img_file'])) {
        $accessToken = $weixinAccess->getAccessToken();
        $file = $_FILES['img_file'];
        $fileName = $file['name'];
        $tmpFilePath = $file['tmp_name'];
        $filePath = $uploadDir . basename($fileName);

        if (move_uploaded_file($tmpFilePath, $filePath)) {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                ob_start();
                $material->upload($accessToken, $filePath, 'image');
                $uploadOutput = ob_get_clean();
                $uploadResult = json_decode($uploadOutput, true);
                if (isset($uploadResult['media_id'])) {
                    $uploadedImageUrl = $uploadResult['media_id'];
                    $jsonOutput = "Image uploaded successfully: " . $uploadedImageUrl;
                } else {
                    $jsonOutput = "Upload failed: " . $uploadOutput;
                }
                unlink($filePath);
            } else {
                $jsonOutput = "Unsupported image format: $extension (only jpg, jpeg, png, gif allowed)";
                unlink($filePath);
            }
        } else {
            $jsonOutput = "Image move failed. Check server permissions.";
        }
    }

    // Add News (Draft)
    if (isset($_POST['news_create'])) {
        $accessToken = $weixinAccess->getAccessToken();
        $newsData = $_POST['json_output'] ?? json_encode(['articles' => $articles], JSON_UNESCAPED_UNICODE);
        if ($newsData) {
            ob_start();
            $draft->add($accessToken, $newsData);
            $jsonOutput = ob_get_clean();
        } else {
            $jsonOutput = "No articles to add.";
        }
    }

    // Get News
    if (isset($_POST['news_get'])) {
      //  $accessToken = $weixinAccess->getStabletoken();
          $accessToken = $weixinAccess->getAccessToken();
        $mediaId = $_POST['media_id'] ?? '';
        if ($mediaId) {
            ob_start();
            $draft->get($accessToken, $mediaId);
            $jsonOutput = ob_get_clean();
        } else {
            $jsonOutput = "Please enter a valid Media ID.";
        }
    }

    // Update News
    if (isset($_POST['news_update'])) {
        $accessToken = $weixinAccess->getAccessToken();
        $newsData = $_POST['json_output'] ?? '';
        if ($newsData) {
            ob_start();
            $draft->update($accessToken, $newsData);
            $jsonOutput = ob_get_clean();
        } else {
            $jsonOutput = "No JSON data to update.";
        }
    }

    // Batch Get News
    if (isset($_POST['news_batch_get'])) {
        $accessToken = $weixinAccess->getAccessToken();
        ob_start();
        $draft->batchGet($accessToken);
        $jsonOutput = ob_get_clean();
    }

    // Generate JSON and handle Markdown to HTML conversion
    for ($i = 0; $i < $articleCount; $i++) {
        $articleType = $_POST["article_type_$i"] ?? 'news';
        $content = $_POST["content_$i"] ?? '';

        if (isset($_POST["convert_md_$i"])) {
            $convertedContents[$i] = markdownToHtml($content);
            $content = fixMarkdownTableInHtml($convertedContents[$i]);
            $articles[] = [
                'article_type' => $articleType,
                'title' => $_POST["title_$i"] ?? '',
                'content' => $content,
                'thumb_media_id' => ($i === 0 && $uploadedImageUrl) ? $uploadedImageUrl : ($_POST["thumb_media_id_$i"] ?? '')
            ];
            $jsonOutput = json_encode(['articles' => $articles], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $article = [
                'article_type' => $articleType,
                'title' => $_POST["title_$i"] ?? '',
                'content' => $content
            ];

            if ($articleType === 'news') {
                $article['author'] = $_POST["author_$i"] ?? '';
                $article['digest'] = $_POST["digest_$i"] ?? '';
                $article['content_source_url'] = $_POST["content_source_url_$i"] ?? '';
                $article['thumb_media_id'] = ($i === 0 && $uploadedImageUrl) ? $uploadedImageUrl : ($_POST["thumb_media_id_$i"] ?? '');
                $article['need_open_comment'] = (int)($_POST["need_open_comment_$i"] ?? 0);
                $article['only_fans_can_comment'] = (int)($_POST["only_fans_can_comment_$i"] ?? 0);
                $article['pic_crop_235_1'] = $_POST["pic_crop_235_1_$i"] ?? '';
                $article['pic_crop_1_1'] = $_POST["pic_crop_1_1_$i"] ?? '';
            } elseif ($articleType === 'newspic') {
                $article['need_open_comment'] = (int)($_POST["need_open_comment_$i"] ?? 0);
                $article['only_fans_can_comment'] = (int)($_POST["only_fans_can_comment_$i"] ?? 0);
                $article['image_info'] = [
                    'image_list' => [
                        ['image_media_id' => $_POST["image_media_id_$i"] ?? '']
                    ]
                ];
                $article['cover_info'] = [
                    'crop_percent_list' => [
                        [
                            'ratio' => '1_1',
                            'x1' => $_POST["crop_x1_1_1_$i"] ?? '0',
                            'y1' => $_POST["crop_y1_1_1_$i"] ?? '0',
                            'x2' => $_POST["crop_x2_1_1_$i"] ?? '1',
                            'y2' => $_POST["crop_y2_1_1_$i"] ?? '1'
                        ]
                    ]
                ];
                $article['product_info'] = [
                    'footer_product_info' => [
                        'product_key' => $_POST["product_key_$i"] ?? ''
                    ]
                ];
            }
            $articles[] = array_filter($article, fn($value) => $value !== '');
        }
    }

    if (isset($_POST['generate_json']) || (!isset($_POST['news_create']) && !isset($_POST['upload_img']) && !isset($_POST['convert_md_0']) && !isset($_POST['news_get']) && !isset($_POST['news_update']) && !isset($_POST['news_batch_get']))) {
        $jsonOutput = json_encode(['articles' => $articles], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeChat API JSON Generator with Markdown</title>
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --bg-color: #f8f9fa;
            --text-color: #333;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .article-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input[type="text"], textarea, select, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
            font-family: 'Courier New', Courier, monospace;
        }

        .content-group {
            position: relative;
        }

        .convert-btn {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }

        .convert-btn:hover {
            background-color: #218838;
        }

        .checkbox-group {
            margin: 10px 0;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .output-btn-group {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        button {
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        #json-output {
            width: 100%;
            min-height: 200px;
            background-color: #f1f3f5;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            margin-top: 20px;
            font-family: 'Courier New', Courier, monospace;
            resize: vertical;
        }

        @media (max-width: 600px) {
            body { padding: 20px 10px; }
            h1 { font-size: 1.8em; }
            .btn-group, .output-btn-group { flex-direction: column; }
            button, .convert-btn { width: 100%; margin-left: 0; margin-top: 10px; }
            input[type="file"], input[type="text"] { width: 100%; margin: 0; }
        }
    </style>
</head>
<body>
    <h1>微信公众号草稿发布功能接口 <a href="https://developers.weixin.qq.com/doc/offiaccount/Draft_Box/Add_draft.html" target="_blank">参考文档</a></h1>
    
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <label for="article_count">Number of Articles:</label>
            <input type="number" name="article_count" id="article_count" min="1" value="<?php echo isset($_POST['article_count']) ? $_POST['article_count'] : 1; ?>" onchange="this.form.submit()">

            <?php
            $articleCount = isset($_POST['article_count']) ? (int)$_POST['article_count'] : 1;
            for ($i = 0; $i < $articleCount; $i++): ?>
                <div class="article-section">
                    <h3>Article <?php echo $i + 1; ?></h3>
                    <label for="article_type_<?php echo $i; ?>">Article Type:</label>
                    <select name="article_type_<?php echo $i; ?>" id="article_type_<?php echo $i; ?>" onchange="this.form.submit()">
                        <option value="news" <?php echo (($_POST["article_type_$i"] ?? 'news') === 'news') ? 'selected' : ''; ?>>News (图文消息)</option>
                        <option value="newspic" <?php echo (($_POST["article_type_$i"] ?? '') === 'newspic') ? 'selected' : ''; ?>>Newspic (图片消息)</option>
                    </select>

                    <label for="title_<?php echo $i; ?>">Title:</label>
                    <input type="text" name="title_<?php echo $i; ?>" id="title_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["title_$i"] ?? ''); ?>" required>

                    <div class="content-group">
                        <label for="content_<?php echo $i; ?>">Content:</label>
                        <textarea name="content_<?php echo $i; ?>" id="content_<?php echo $i; ?>" required><?php echo htmlspecialchars($convertedContents[$i] ?? ($_POST["content_$i"] ?? '')); ?></textarea>
                        <button type="submit" name="convert_md_<?php echo $i; ?>" value="1" class="convert-btn">Markdown to HTML</button>
                    </div>

                    <?php if (($_POST["article_type_$i"] ?? 'news') === 'news'): ?>
                        <label for="author_<?php echo $i; ?>">Author:</label>
                        <input type="text" name="author_<?php echo $i; ?>" id="author_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["author_$i"] ?? ''); ?>">

                        <label for="digest_<?php echo $i; ?>">Digest:</label>
                        <input type="text" name="digest_<?php echo $i; ?>" id="digest_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["digest_$i"] ?? ''); ?>">

                        <label for="content_source_url_<?php echo $i; ?>">Content Source URL:</label>
                        <input type="text" name="content_source_url_<?php echo $i; ?>" id="content_source_url_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["content_source_url_$i"] ?? ''); ?>">

                        <label for="thumb_media_id_<?php echo $i; ?>">Thumb Media ID:</label>
                        <input type="text" name="thumb_media_id_<?php echo $i; ?>" id="thumb_media_id_<?php echo $i; ?>" value="<?php echo htmlspecialchars(($i === 0 && $uploadedImageUrl) ? $uploadedImageUrl : ($_POST["thumb_media_id_$i"] ?? '')); ?>" required>

                        <div class="checkbox-group">
                            <label><input type="checkbox" name="need_open_comment_<?php echo $i; ?>" value="1" <?php echo (($_POST["need_open_comment_$i"] ?? 0) == 1) ? 'checked' : ''; ?>> Open Comments</label>
                            <label><input type="checkbox" name="only_fans_can_comment_<?php echo $i; ?>" value="1" <?php echo (($_POST["only_fans_can_comment_$i"] ?? 0) == 1) ? 'checked' : ''; ?>> Only Fans Can Comment</label>
                        </div>

                        <label for="pic_crop_235_1_<?php echo $i; ?>">Pic Crop 2.35:1 (X1_Y1_X2_Y2):</label>
                        <input type="text" name="pic_crop_235_1_<?php echo $i; ?>" id="pic_crop_235_1_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["pic_crop_235_1_$i"] ?? ''); ?>" placeholder="e.g., 0.1945_0_1_0.5236">

                        <label for="pic_crop_1_1_<?php echo $i; ?>">Pic Crop 1:1 (X1_Y1_X2_Y2):</label>
                        <input type="text" name="pic_crop_1_1_<?php echo $i; ?>" id="pic_crop_1_1_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["pic_crop_1_1_$i"] ?? ''); ?>" placeholder="e.g., 0_0_1_1">
                    <?php else: ?>
                        <label for="image_media_id_<?php echo $i; ?>">Image Media ID:</label>
                        <input type="text" name="image_media_id_<?php echo $i; ?>" id="image_media_id_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["image_media_id_$i"] ?? ''); ?>" required>

                        <label>Cover Crop 1:1:</label>
                        <input type="text" name="crop_x1_1_1_<?php echo $i; ?>" placeholder="X1" value="<?php echo htmlspecialchars($_POST["crop_x1_1_1_$i"] ?? '0'); ?>" style="width: 24%;">
                        <input type="text" name="crop_y1_1_1_<?php echo $i; ?>" placeholder="Y1" value="<?php echo htmlspecialchars($_POST["crop_y1_1_1_$i"] ?? '0'); ?>" style="width: 24%;">
                        <input type="text" name="crop_x2_1_1_<?php echo $i; ?>" placeholder="X2" value="<?php echo htmlspecialchars($_POST["crop_x2_1_1_$i"] ?? '1'); ?>" style="width: 24%;">
                        <input type="text" name="crop_y2_1_1_<?php echo $i; ?>" placeholder="Y2" value="<?php echo htmlspecialchars($_POST["crop_y2_1_1_$i"] ?? '1'); ?>" style="width: 24%;">

                        <label for="product_key_<?php echo $i; ?>">Product Key:</label>
                        <input type="text" name="product_key_<?php echo $i; ?>" id="product_key_<?php echo $i; ?>" value="<?php echo htmlspecialchars($_POST["product_key_$i"] ?? ''); ?>">

                        <div class="checkbox-group">
                            <label><input type="checkbox" name="need_open_comment_<?php echo $i; ?>" value="1" <?php echo (($_POST["need_open_comment_$i"] ?? 0) == 1) ? 'checked' : ''; ?>> Open Comments</label>
                            <label><input type="checkbox" name="only_fans_can_comment_<?php echo $i; ?>" value="1" <?php echo (($_POST["only_fans_can_comment_$i"] ?? 0) == 1) ? 'checked' : ''; ?>> Only Fans Can Comment</label>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <div class="btn-group">
                <input type="file" name="img_file" accept=".jpg,.jpeg,.png,.gif" style="width: auto;">
                <button type="submit" name="upload_img">上传封面</button>
                <button type="submit" name="generate_json">直接生成 JSON</button>
            </div>

            <?php if ($jsonOutput): ?>
                <h2>Output:</h2>
                <textarea name="json_output" id="json-output"><?php echo htmlspecialchars($jsonOutput); ?></textarea>
                <div class="output-btn-group">
                    <button type="submit" name="news_create">发布草稿</button>
                    <input type="text" name="media_id" placeholder="Media ID" value="<?php echo htmlspecialchars($_POST['media_id'] ?? ''); ?>" style="width: 150px;">
                    <button type="submit" name="news_get">取草稿</button>
                    <button type="submit" name="news_update">修改草稿</button>
                    <button type="submit" name="news_batch_get">批量取草稿</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>