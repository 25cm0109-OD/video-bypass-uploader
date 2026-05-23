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

const updateSelectedFile = () => {
  const file = fileInput.files?.[0];
  if (file) {
    selectedFile.textContent = file.name;
  } else {
    selectedFile.innerHTML = defaultSelectedHtml;
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
  if (!file) {
    message.textContent = "動画ファイルを選択してください。";
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
