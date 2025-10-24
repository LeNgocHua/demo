(function(){
  var script = document.currentScript;
  var endpoint = script && script.getAttribute('data-endpoint'); // can be base e.g. https://site.com/wp-json/huadev/v1/presets
  var presetSlug = script && script.getAttribute('data-preset');
  var bg = script && script.getAttribute('data-bg');
  var env = script && script.getAttribute('data-envelope');
  var p1 = script && script.getAttribute('data-pocket1');
  var p2 = script && script.getAttribute('data-pocket2');
  var emoji = script && script.getAttribute('data-emoji');
  var image = script && script.getAttribute('data-image');
  var sealUrl = script && script.getAttribute('data-seal-url');
  var floating = script && script.getAttribute('data-float');

  function createContainer(){
    var container = document.createElement('div');
    container.className = 'huadev';
    return container;
  }
  function injectStyles(){
    if (document.getElementById('huadev-embed-style')) return;
    var style = document.createElement('style');
    style.id = 'huadev-embed-style';
    style.textContent = "" +
      ".huadev{display:flex;align-items:center;justify-content:center;min-height:320px;padding:24px;}" +
      ".huadev .envelope{position:relative;width:360px;height:240px;border-bottom-left-radius:6px;border-bottom-right-radius:6px;margin-left:auto;margin-right:auto;top:0;box-shadow:rgba(0,0,0,.2) 0 4px 20px;transition:transform .3s cubic-bezier(.25,.46,.45,.94);cursor:pointer;z-index:1;}" +
      ".huadev .envelope.floating{animation:float 3s ease-in-out infinite;}" +
      ".huadev .flap{position:absolute;width:0;height:0;z-index:3;border-width:130px 180px 110px;border-style:solid;transform-origin:center top;transition:transform .8s cubic-bezier(.25,.46,.45,.94) .6s, z-index .6s;}" +
      ".huadev .envelope.open .flap{transform:rotateX(180deg);transition:transform 1.2s cubic-bezier(.25,.46,.45,.94), z-index 1.2s;z-index:0;}" +
      ".huadev .pocket{position:absolute;width:0;height:0;z-index:3;border-width:120px 180px;border-style:solid;border-bottom-left-radius:6px;border-bottom-right-radius:6px;}" +
      ".huadev .seal{position:absolute;top:45%;left:50%;transform:translate(-50%,-50%);width:48px;height:48px;background:radial-gradient(circle at 30% 30%,#b56a3a,#733d1d);border-radius:50%;box-shadow:inset 2px 2px 6px rgba(255,255,255,.3), inset -2px -2px 6px rgba(0,0,0,.3);z-index:4;}" +
      ".huadev .seal::after{content:attr(data-emoji);position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:20px;}" +
      ".huadev .letter{position:relative;background-color:#fff;width:90%;margin-left:auto;margin-right:auto;height:90%;top:5%;border-radius:6px;box-shadow:rgba(0,0,0,.12) 0 2px 26px;transition:box-shadow .3s;transform:translateY(0);transition:transform .6s cubic-bezier(.25,.46,.45,.94) .2s, z-index .2s;z-index:1;}" +
      ".huadev .envelope.open .letter{box-shadow:rgba(0,0,0,.2) 0 4px 30px;transform:translateY(-114px);transition:transform 1s cubic-bezier(.25,.46,.45,.94) .8s, z-index .8s;z-index:2;}" +
      ".huadev .letter img{border-radius:6px;display:block;width:100%;height:100%;object-fit:cover;}" +
      "@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}";
    document.head.appendChild(style);
  }

  function render(opts){
    injectStyles();
    var container = createContainer();
    container.style.background = opts.bg_color || '#f8f4f2';
    var envelopeEl = document.createElement('div');
    envelopeEl.className = 'envelope' + (opts.float ? ' floating' : '');
    envelopeEl.style.backgroundColor = opts.envelope_color || '#812927';
    envelopeEl.onclick = function(){ envelopeEl.classList.toggle('open'); };

    var flap = document.createElement('div');
    flap.className = 'flap';
    flap.style.borderColor = (opts.envelope_color || '#812927') + ' transparent transparent';

    var pocket = document.createElement('div');
    pocket.className = 'pocket';
    pocket.style.borderColor = 'transparent ' + (opts.pocket_color1 || '#a33f3d') + ' ' + (opts.pocket_color2 || '#a84644');

    var seal = document.createElement('div');
    seal.className = 'seal';
    if (opts.seal_url) {
      seal.style.backgroundImage = "url('" + opts.seal_url + "')";
      seal.style.backgroundSize = 'cover';
      seal.style.backgroundPosition = 'center';
    } else {
      seal.setAttribute('data-emoji', opts.seal_emoji || '💍');
    }

    var letter = document.createElement('div');
    letter.className = 'letter';
    var img = document.createElement('img');
    img.src = opts.image_url || '';
    img.alt = 'Envelope image';
    img.width = 100; // attributes not CSS sizing
    img.height = 100;
    letter.appendChild(img);

    envelopeEl.appendChild(flap);
    envelopeEl.appendChild(pocket);
    envelopeEl.appendChild(seal);
    envelopeEl.appendChild(letter);
    container.appendChild(envelopeEl);

    script.parentNode.insertBefore(container, script);
  }

  function coerceBool(v){ if (v === null || v === undefined) return undefined; return v === '1' || v === 'true' || v === true; }

  function start(opts){ render(opts); }

  function fromAttributes(){
    return {
      bg_color: bg,
      envelope_color: env,
      pocket_color1: p1,
      pocket_color2: p2,
      seal_emoji: emoji,
      seal_url: sealUrl,
      image_url: image,
      float: coerceBool(floating)
    };
  }

  function buildUrl(ep, slug){
    if (!ep) return null;
    var url = String(ep);
    // Trim trailing slash for consistent concatenation
    if (url.length > 1 && url[url.length - 1] === '/') url = url.slice(0, -1);
    if (!slug) return url;
    if (/\/presets(\/|$)/.test(url)) {
      return url + '/' + encodeURIComponent(slug);
    }
    if (/\/huadev\/v1$/.test(url)) {
      return url + '/presets/' + encodeURIComponent(slug);
    }
    if (/\/wp-json$/.test(url)) {
      return url + '/huadev/v1/presets/' + encodeURIComponent(slug);
    }
    // Fallback: don't modify if unknown format
    return url;
  }

  function fetchOptions(){
    if (!endpoint) return Promise.resolve(null);
    var url = buildUrl(endpoint, presetSlug);
    return fetch(url, { credentials: 'omit' })
      .then(function(r){ return r.json(); })
      .then(function(j){ return j && j.data ? j.data : null; })
      .catch(function(){ return null; });
  }

  var attrOpts = fromAttributes();
  var hasAnyAttr = Object.keys(attrOpts).some(function(k){ return attrOpts[k] !== undefined && attrOpts[k] !== null; });

  if (endpoint) {
    fetchOptions().then(function(remote){ start(Object.assign({}, remote || {}, attrOpts)); });
  } else if (hasAnyAttr) {
    start(attrOpts);
  }
})();
