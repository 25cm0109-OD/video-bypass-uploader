const form = document.getElementById("upload-form");
const fileInput = document.getElementById("file");
const dropzone = document.getElementById("dropzone");
const browseButton = document.getElementById("browse-button");
const selectedFile = document.getElementById("selected-file");
const uploadButton = document.getElementById("upload-button");
const progress = document.getElementById("progress");
const progressBar = document.getElementById("progress-bar");
const message = document.getElementById("message");
const result = document.getElementById("result");
const resultUrl = document.getElementById("result-url");
const copyButton = document.getElementById("copy-button");
const defaultSelectedHtml = selectedFile.innerHTML;
const maxFileBytes = 500 * 1024 * 1024;
const allowedExtensions = new Set(["mp4", "webm", "ogg", "mov"]);

const formatBytes = (bytes) => {
  const units = ["B", "KB", "MB", "GB"];
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
};

const validateFile = (file) => {
  if (!file) return "動画ファイルを選択してください。";

  const extension = file.name.split(".").pop()?.toLowerCase() || "";
  if (!allowedExtensions.has(extension)) {
    return "mp4, webm, ogg, mov の動画ファイルを選択してください。";
  }

  if (file.size > maxFileBytes) {
    return `ファイルサイズは500MB以下にしてください。選択中: ${formatBytes(file.size)}`;
  }

  return "";
};

const updateSelectedFile = () => {
  const file = fileInput.files?.[0];
  if (file) {
    selectedFile.textContent = `${file.name} (${formatBytes(file.size)})`;
    const validationMessage = validateFile(file);
    message.textContent = validationMessage;
  } else {
    selectedFile.innerHTML = defaultSelectedHtml;
    message.textContent = "";
  }
};

const resetUi = () => {
  progress.hidden = true;
  progressBar.style.width = "0%";
  message.textContent = "";
  result.hidden = true;
  resultUrl.value = "";
};

const setBusy = (busy) => {
  uploadButton.disabled = busy;
  fileInput.disabled = busy;
  browseButton.disabled = busy;
  dropzone.setAttribute("aria-disabled", busy ? "true" : "false");
  dropzone.classList.toggle("is-disabled", busy);
  uploadButton.textContent = busy ? "アップロード中..." : "アップロード";
};

copyButton.addEventListener("click", async () => {
  if (!resultUrl.value) return;
  try {
    await navigator.clipboard.writeText(resultUrl.value);
    message.textContent = "コピーしました。";
  } catch {
    resultUrl.select();
    document.execCommand("copy");
    message.textContent = "コピーしました。";
  }
});

const openFileDialog = () => {
  if (fileInput.disabled) return;
  fileInput.click();
};

browseButton.addEventListener("click", (event) => {
  event.stopPropagation();
  openFileDialog();
});

dropzone.addEventListener("click", openFileDialog);

dropzone.addEventListener("keydown", (event) => {
  if (event.key === "Enter" || event.key === " ") {
    event.preventDefault();
    openFileDialog();
  }
});

["dragenter", "dragover"].forEach((eventName) => {
  dropzone.addEventListener(eventName, (event) => {
    event.preventDefault();
    dropzone.classList.add("is-dragover");
  });
});

["dragleave", "dragend", "drop"].forEach((eventName) => {
  dropzone.addEventListener(eventName, (event) => {
    event.preventDefault();
    dropzone.classList.remove("is-dragover");
  });
});

dropzone.addEventListener("drop", (event) => {
  const files = event.dataTransfer?.files;
  if (files && files.length > 0) {
    fileInput.files = files;
    updateSelectedFile();
  }
});

fileInput.addEventListener("change", updateSelectedFile);
updateSelectedFile();

form.addEventListener("submit", (event) => {
  event.preventDefault();
  resetUi();

  const file = fileInput.files[0];
  const validationMessage = validateFile(file);
  if (validationMessage) {
    message.textContent = validationMessage;
    return;
  }

  const formData = new FormData();
  formData.append("file", file);

  const xhr = new XMLHttpRequest();
  const scriptTag =
    document.currentScript ||
    document.querySelector('script[src$="assets/app.js"]');
  const apiUrl = scriptTag
    ? new URL("../api/upload.php", scriptTag.src).toString()
    : new URL("api/upload.php", window.location.href).toString();
  xhr.open("POST", apiUrl);
  xhr.responseType = "json";

  xhr.upload.onprogress = (progressEvent) => {
    if (!progressEvent.lengthComputable) return;
    progress.hidden = false;
    const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
    progressBar.style.width = `${percent}%`;
  };

  xhr.onload = () => {
    setBusy(false);
    if (xhr.status < 200 || xhr.status >= 300) {
      const errorMessage =
        (xhr.response && xhr.response.error) ||
        (xhr.status === 413
          ? "ファイルサイズがサーバー上限を超えています。500MB以下の動画を選択してください。"
          : "") ||
        "アップロードに失敗しました。";
      message.textContent = errorMessage;
      return;
    }

    const data = xhr.response || {};
    if (!data.url) {
      message.textContent = "アップロードURLを取得できませんでした。";
      return;
    }

    resultUrl.value = data.url;
    result.hidden = false;
    message.textContent =
      "アップロード完了。リンクをDiscordに貼り付けてください。";
  };

  xhr.onerror = () => {
    setBusy(false);
    message.textContent = "通信エラーが発生しました。";
  };

  setBusy(true);
  xhr.send(formData);
});
