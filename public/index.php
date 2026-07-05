<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Discord Video Uploader</title>
    <link rel="stylesheet" href="assets/style.css" />
  </head>
  <body>
    <main class="upload-card">
      <h1 class="title">Discord 動画アップローダー</h1>
      <p class="meta">最大500MB / mp4, webm, ogg, mov</p>

      <form id="upload-form" class="upload-form">
        <div class="drop-zone" id="dropzone" role="button" tabindex="0">
          <input
            type="file"
            id="file"
            name="file"
            class="file-input"
            accept=".mp4,.webm,.ogg,.mov,video/mp4,video/webm,video/ogg,video/quicktime"
            required
          />
          <p class="drop-zone-text">Drag &amp; Drop or Browse Files</p>
          <button type="button" class="browse-btn" id="browse-button">
            ファイルを選択
          </button>
          <p class="selected-file" id="selected-file">選択されていません</p>
        </div>
        <button type="submit" id="upload-button" class="upload-btn">
          アップロード
        </button>
      </form>

      <div class="progress" id="progress" hidden>
        <div class="progress-bar" id="progress-bar"></div>
      </div>

      <p class="message" id="message" role="status"></p>

      <div class="result" id="result" hidden>
        <label for="result-url">Share Link</label>
        <div class="result-row">
          <input type="text" id="result-url" readonly />
          <button type="button" id="copy-button">
            Copy
          </button>
        </div>
      </div>
    </main>

    <script src="assets/app.js" defer></script>
  </body>
</html>
