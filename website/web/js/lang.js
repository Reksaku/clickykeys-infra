const translations = {};
const DEFAULT_LANG = "en";


function loadLanguageFile(lang) {
  return new Promise(resolve => {
    const script = document.createElement("script");
    script.src = `js/lang/${lang}.js`;
    script.onload = () => {
      translations[lang] = window[`lang_${lang}`];
      resolve();
    };
    document.head.appendChild(script);
  });
}

function applyTranslations(lang) {
  if (!translations[lang]) return;

  document.documentElement.lang = lang;

  document.querySelectorAll("[data-i18n]").forEach(el => {
    const key = el.getAttribute("data-i18n");
    const value = getMessage(lang, key);
    el.innerHTML = value;
  });

  document.querySelectorAll("[data-i18n-html]").forEach(el => {
    const key = el.getAttribute("data-i18n-html");
    const value = getMessage(lang, key);
    el.innerHTML = value;
  });

  document.querySelectorAll(".lang-btn").forEach(btn => {
    btn.classList.toggle("active", btn.getAttribute("data-lang") === lang);
  });

  localStorage.setItem("clickykeys-lang", lang);
}


function getMessage(lang, key) {
  const dict = translations[lang] || {};
  const fallbackDict = translations[DEFAULT_LANG] || {};

  if (dict[key] != null) return dict[key];

  if (fallbackDict[key] != null) return fallbackDict[key];

  return key;
}


document.addEventListener("DOMContentLoaded", async () => {
  const saved = localStorage.getItem("clickykeys-lang") || DEFAULT_LANG;

  await loadLanguageFile(DEFAULT_LANG);

  if (saved !== DEFAULT_LANG) {
    await loadLanguageFile(saved);
  }

  applyTranslations(saved);

  document.querySelectorAll(".lang-btn").forEach(btn => {
    btn.addEventListener("click", async () => {
      const lang = btn.getAttribute("data-lang");

      if (!translations[lang]) {
        await loadLanguageFile(lang);
      }
      applyTranslations(lang);
    });
  });
});
