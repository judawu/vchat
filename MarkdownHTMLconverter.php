<?php
require_once __DIR__ . '/vendor/autoload.php';

use League\CommonMark\CommonMarkConverter; // For Markdown to HTML
use League\HTMLToMarkdown\HtmlConverter;   // For HTML to Markdown

function markdownToHtml($markdown) {
    $converter = new CommonMarkConverter();
    return $converter->convert($markdown)->getContent(); // Convert and get HTML string
}

function htmlToMarkdown($html) {
    $converter = new HtmlConverter(); // This should now work
    return $converter->convert($html);
}

// Process form submission
$result = '';
$inputText = '';
$conversionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputText = $_POST['input_text'] ?? '';
    $conversionType = $_POST['conversion_type'] ?? '';
    
    if ($conversionType === 'md_to_html') {
        $result = markdownToHtml($inputText);
    } elseif ($conversionType === 'html_to_md') {
        $result = htmlToMarkdown($inputText);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markdown ↔ HTML Converter</title>
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
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        h3 {
            color: var(--secondary-color);
            margin-top: 20px;
            font-size: 1.5em;
        }

        form {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        textarea {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1em;
            font-family: 'Courier New', Courier, monospace;
            resize: vertical;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }

        textarea[readonly] {
            background-color: #f1f3f5;
            color: #555;
        }

        .btn-group {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        button {
            padding: 12px 25px;
            font-size: 1.1em;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 10px;
            }
            h1 {
                font-size: 2em;
            }
            .btn-group {
                flex-direction: column;
            }
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <h1>Markdown ↔ HTML Converter</h1>
    
    <form method="post">
        <textarea name="input_text" placeholder="Enter your text here..."><?php echo htmlspecialchars($inputText); ?></textarea>
        
        <div class="btn-group">
            <button type="submit" name="conversion_type" value="md_to_html">Markdown to HTML</button>
            <button type="submit" name="conversion_type" value="html_to_md">HTML to Markdown</button>
        </div>
        
        <?php if ($result): ?>
            <h3>Result:</h3>
            <textarea readonly><?php echo htmlspecialchars($result); ?></textarea>
        <?php endif; ?>
    </form>
</body>
</html>