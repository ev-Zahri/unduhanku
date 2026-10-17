/**
 * Unduhanku - Client Application Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const form = document.getElementById('download-form');
    const urlInput = document.getElementById('media-url-input');
    const btnPaste = document.getElementById('btn-paste');
    const btnAnalyze = document.getElementById('btn-analyze');
    const analyzeSpinner = document.getElementById('analyze-spinner');

    const processingState = document.getElementById('processing-state');
    const resultCard = document.getElementById('result-card');

    const mediaThumbnail = document.getElementById('media-thumbnail');
    const mediaDuration = document.getElementById('media-duration');
    const mediaPlatform = document.getElementById('media-platform');
    const mediaTitle = document.getElementById('media-title');
    const mediaUploader = document.getElementById('media-uploader');
    const mediaViews = document.getElementById('media-views');

    const videoFormatsContainer = document.getElementById('video-formats-container');
    const audioFormatContainer = document.getElementById('audio-format-container');
    const galleryContainer = document.getElementById('gallery-container');
    const tabGalleryBtn = document.getElementById('tab-gallery-btn');
    const downloadProgressBox = document.getElementById('download-progress-box');
    const downloadStatusText = document.getElementById('download-status-text');



    let currentMediaData = null;

    // -------------------------------------------------------------
    // Inisialisasi & Health Check
    // -------------------------------------------------------------
    checkSystemStatus();
    preloadActiveAd();

    // -------------------------------------------------------------
    // Sistem Iklan Afiliasi Klien (Maksimal 3x Tayang per Hari per Klien)
    // -------------------------------------------------------------
    let cachedActiveAd = null;
    let lastAdTriggerTime = 0;
    const MAX_AD_TRIGGERS_PER_DAY = 3;

    async function preloadActiveAd() {
        try {
            const res = await fetch('api/ad/serve');
            if (res.ok) {
                const json = await res.json();
                if (json.success && json.data && json.data.url) {
                    cachedActiveAd = json.data;
                }
            }
        } catch (e) {
            // Abaikan jika network offline
        }
    }

    function getAdDailyQuota() {
        const today = new Date().toISOString().slice(0, 10);
        try {
            const raw = localStorage.getItem('unduhanku_ad_quota');
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed.date === today) {
                    return parsed.count || 0;
                }
            }
        } catch (e) {}
        return 0;
    }

    function incrementAdDailyQuota() {
        const today = new Date().toISOString().slice(0, 10);
        const currentCount = getAdDailyQuota();
        const nextCount = currentCount + 1;
        try {
            localStorage.setItem('unduhanku_ad_quota', JSON.stringify({
                date: today,
                count: nextCount
            }));
        } catch (e) {}
        return nextCount;
    }

    async function triggerClientAd() {
        const now = Date.now();
        if (now - lastAdTriggerTime < 1500) return; // Cegah double trigger simultan
        lastAdTriggerTime = now;

        const currentCount = getAdDailyQuota();
        if (currentCount >= MAX_AD_TRIGGERS_PER_DAY) {
            console.log(`[Ad] Batas kuota iklan harian telah tercapai (${currentCount}/${MAX_AD_TRIGGERS_PER_DAY}). Iklan tidak ditayangkan.`);
            return;
        }

        // Jika cache belum terisi, coba ambil sekarang juga
        if (!cachedActiveAd || !cachedActiveAd.url) {
            await preloadActiveAd();
        }

        if (!cachedActiveAd || !cachedActiveAd.url) {
            console.log('[Ad] Tidak ada iklan aktif di database atau belum siap.');
            return;
        }

        // Buka iklan di tab baru langsung dari gesture klik user (metode aman tanpa dicegat popup blocker)
        try {
            window.open(cachedActiveAd.url, '_blank', 'noopener,noreferrer');
            console.log(`[Ad] Berhasil membuka iklan: "${cachedActiveAd.product_name || 'Afiliasi'}" (Tayang ${currentCount + 1}/${MAX_AD_TRIGGERS_PER_DAY})`);
        } catch (e) {
            console.warn('[Ad] Browser memblokir pembukaan tab baru:', e);
        }

        // Catat kuota dan kirim hit klik ke server
        incrementAdDailyQuota();

        const adId = cachedActiveAd.id;
        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify({ ad_id: adId })], { type: 'application/json' });
                navigator.sendBeacon('api/ad/click', blob);
            } else {
                fetch('api/ad/click', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ad_id: adId }),
                    keepalive: true
                });
            }
        } catch (e) {}

        // Preload kembali iklan aktif berikutnya
        preloadActiveAd();
    }

    // Pasang delegasi klik untuk mendeteksi pengunduhan media apapun
    document.addEventListener('click', (e) => {
        const dlBtn = e.target.closest('.btn-dl-image, .btn-slide-download, .format-btn, #btn-carousel-audio, #btn-download-all, .btn-download-item, #btn-dl-audio');
        if (dlBtn) {
            triggerClientAd();
        }
    });

    // -------------------------------------------------------------
    // Clipboard Paste Button
    // -------------------------------------------------------------
    btnPaste.addEventListener('click', async () => {
        triggerClientAd();
        try {
            const text = await navigator.clipboard.readText();
            if (text && (text.startsWith('http://') || text.startsWith('https://'))) {
                urlInput.value = text;
                showToast('Tautan berhasil ditempel dari clipboard!', 'success');
                // Otomatis trigger analisis
                form.dispatchEvent(new Event('submit'));
            } else {
                showToast('Teks di clipboard bukan URL yang valid.', 'error');
            }
        } catch (err) {
            urlInput.focus();
            showToast('Izin akses clipboard ditolak atau tidak didukung.', 'error');
        }
    });

    // -------------------------------------------------------------
    // Form Submit: Analisis Media
    // -------------------------------------------------------------
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        triggerClientAd();
        const url = urlInput.value.trim();

        if (!url) {
            showToast('Silakan masukkan URL media sosial.', 'error');
            return;
        }

        // Tampilkan state loading
        setLoading(true);
        resultCard.classList.add('hidden');
        processingState.classList.remove('hidden');

        try {
            const response = await fetch('api/analyze', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url })
            });

            const res = await response.json();

            if (!res.success) {
                throw new Error(res.message || 'Gagal menganalisis link');
            }

            currentMediaData = res.data;
            renderMediaResult(res.data);
            showToast('Media berhasil dianalisis!', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            setLoading(false);
            processingState.classList.add('hidden');
        }
    });

    // -------------------------------------------------------------
    // Render Hasil Analisis
    // -------------------------------------------------------------
    function renderMediaResult(data) {
        mediaTitle.textContent = data.title || 'Media Unduhan';
        mediaUploader.textContent = data.uploader || 'Anonim';
        mediaThumbnail.src = data.thumbnail || 'https://via.placeholder.com/640x360?text=No+Thumbnail';
        mediaPlatform.textContent = data.platform || 'Media';
        mediaDuration.textContent = formatDuration(data.duration || 0);

        if (data.view_count) {
            mediaViews.textContent = Number(data.view_count).toLocaleString('id-ID');
            document.getElementById('meta-views').style.display = 'inline-flex';
        } else {
            document.getElementById('meta-views').style.display = 'none';
        }

        // 1. Render Video Formats
        videoFormatsContainer.innerHTML = '';
        const videos = data.formats?.videos || [];

        if (videos.length === 0) {
            videoFormatsContainer.innerHTML = `
                <div class="format-card" style="grid-column: 1 / -1; justify-content: center; text-align: center; padding: 20px;">
                    <div class="format-info" style="text-align: center;">
                        <div class="res-label">Postingan Foto / Album</div>
                        <div class="size-label" style="margin-top: 4px;">Media ini merupakan postingan foto. Silakan beralih ke tab <strong>Slide Album</strong> atau <strong>Gambar</strong> untuk mengunduh.</div>
                    </div>
                </div>
            `;
        } else {
            videos.forEach(v => {
                const card = document.createElement('div');
                card.className = 'format-card';
                const sizeText = v.filesize 
                    ? (v.is_estimated ? `~ ${formatBytes(v.filesize)}` : formatBytes(v.filesize))
                    : 'Stream dinamis';

                card.innerHTML = `
                    <div class="format-info">
                        <div class="res-label">${v.label}</div>
                        <div class="size-label">${sizeText}</div>
                    </div>
                    <button class="btn-download-item" data-fid="${v.id}" title="Unduh ${v.label}">
                        <i class="fa-solid fa-download"></i>
                    </button>
                `;
                card.querySelector('.btn-download-item').addEventListener('click', () => {
                    if (v.download_url && v.has_audio) {
                        // Direct stream: memangkas beban storage (0 byte di harddisk server)
                        const cleanTitle = (data.title || 'video').replace(/[^a-zA-Z0-9_\-\s]/g, '').trim().substring(0, 50) || 'video';
                        const filename = `${cleanTitle}_${v.resolution || 'HD'}.${v.ext || 'mp4'}`;
                        downloadDirectFile(v.download_url, filename, {
                            title: data.title,
                            platform: data.platform,
                            original_url: currentMediaData?.url || '',
                            filesize: v.filesize || 0
                        });
                        showToast(`Memulai streaming unduhan ${v.label}...`, 'info');
                    } else {
                        // Fallback ke server-side yt-dlp untuk format terpisah / merging
                        triggerDownload(v.id, data.title, 'video');
                    }
                });
                videoFormatsContainer.appendChild(card);
            });
        }

        // 2. Render Audio Format
        const audio = data.formats?.best_audio;
        if (audio) {
            const audioSize = audio.filesize 
                ? (audio.is_estimated ? `~ ${formatBytes(audio.filesize)}` : formatBytes(audio.filesize))
                : (data.duration ? `~ ${formatBytes(Math.floor((128 * 1024 * data.duration) / 8))}` : 'Audio stream');

            audioFormatContainer.innerHTML = `
                <div class="audio-info">
                    <div class="audio-icon"><i class="fa-solid fa-music"></i></div>
                    <div>
                        <div class="res-label">${audio.label}</div>
                        <div class="size-label">Format Audio MP3 • ${audioSize}</div>
                    </div>
                </div>
                <button class="btn-primary-sm" id="btn-dl-audio">
                    <i class="fa-solid fa-download"></i> Unduh Audio
                </button>
            `;
            document.getElementById('btn-dl-audio').addEventListener('click', () => {
                triggerDownload('best_audio', data.title, 'audio');
            });
        } else {
            const fallbackAudioSize = data.duration 
                ? `~ ${formatBytes(Math.floor((128 * 1024 * data.duration) / 8))}` 
                : 'Stream audio';

            audioFormatContainer.innerHTML = `
                <div class="audio-info">
                    <div class="audio-icon"><i class="fa-solid fa-music"></i></div>
                    <div>
                        <div class="res-label">Audio MP3</div>
                        <div class="size-label">Ekstrak audio dari video • ${fallbackAudioSize}</div>
                    </div>
                </div>
                <button class="btn-primary-sm" id="btn-dl-audio">
                    <i class="fa-solid fa-download"></i> Unduh Audio
                </button>
            `;
            document.getElementById('btn-dl-audio').addEventListener('click', () => {
                triggerDownload('best_audio', data.title, 'audio');
            });
        }

        // 3. Render Images (if gallery post)
        galleryContainer.innerHTML = '';
        const images = data.formats?.images || [];
        if (images.length > 0) {
            tabGalleryBtn.style.display = 'flex';
            images.forEach((img, idx) => {
                const imgUrl   = typeof img === 'object' ? img.url : img;
                const imgSize  = (typeof img === 'object' && img.filesize) 
                    ? (img.is_estimated ? `~ ${formatBytes(img.filesize)}` : formatBytes(img.filesize))
                    : '~ 350 KB';
                const imgLabel = (typeof img === 'object' && img.label) ? img.label : `Foto #${idx + 1}`;

                const item = document.createElement('div');
                item.className = 'gallery-item';
                const safeName = `${data.title || 'media'}-foto-${idx+1}.jpg`;
                const proxyDownloadUrl = `api/proxy-download?url=${encodeURIComponent(imgUrl)}&name=${encodeURIComponent(safeName)}&title=${encodeURIComponent((data.title || 'Media') + ' - Foto ' + (idx+1))}&platform=${encodeURIComponent(data.platform || 'Web')}&original_url=${encodeURIComponent(currentMediaData?.url || '')}&filesize=${encodeURIComponent(typeof img === 'object' && img.filesize ? img.filesize : 0)}`;

                item.innerHTML = `
                    <img src="${imgUrl}" alt="${imgLabel}" loading="lazy">
                    <div class="gallery-badge">${imgSize}</div>
                    <a href="${proxyDownloadUrl}" class="btn-dl-image" title="Unduh ${imgLabel}">
                        <i class="fa-solid fa-download"></i>
                    </a>
                `;
                galleryContainer.appendChild(item);
            });
        } else {
            tabGalleryBtn.style.display = 'none';
        }

        // 4. Render Itemized Carousel (Slide Cards untuk Media Campuran)
        const tabCarouselBtn = document.getElementById('tab-carousel-btn');
        const carouselCount = document.getElementById('carousel-count');
        const carouselItemsContainer = document.getElementById('carousel-items-container');
        const carouselSummaryDesc = document.getElementById('carousel-summary-desc');
        const btnCarouselAudio = document.getElementById('btn-carousel-audio');
        const btnDownloadAll = document.getElementById('btn-download-all');

        const items = data.items || [];
        const isCarousel = Boolean(data.is_carousel || items.length > 1);

        if (isCarousel && items.length > 0) {
            tabCarouselBtn.classList.remove('hidden');
            carouselCount.textContent = items.length;
            const vidCount = items.filter(i => i.type === 'video').length;
            const imgCount = items.filter(i => i.type === 'image').length;
            carouselSummaryDesc.textContent = `Ditemukan ${items.length} media campuran (${vidCount} Video, ${imgCount} Foto).`;

            // Tombol musik latar
            if (data.formats?.best_audio) {
                btnCarouselAudio.classList.remove('hidden');
                btnCarouselAudio.onclick = () => {
                    triggerDownload('best_audio', `${data.title} - Audio`, 'audio');
                };
            } else {
                btnCarouselAudio.classList.add('hidden');
            }

            // Render setiap slide card
            carouselItemsContainer.innerHTML = '';
            items.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'slide-card';
                const isVideo = (item.type === 'video');
                const badgeClass = isVideo ? 'video' : 'image';
                const badgeIcon = isVideo ? 'fa-video' : 'fa-image';
                const badgeLabel = isVideo ? 'Video' : 'Foto';
                const sizeText = item.filesize ? `~ ${formatBytes(item.filesize)}` : (isVideo ? 'Video HD' : '~ 350 KB');

                card.innerHTML = `
                    <div class="slide-preview">
                        <img src="${item.thumbnail || 'https://via.placeholder.com/300'}" alt="${item.label}" loading="lazy">
                        <div class="slide-type-badge ${badgeClass}">
                            <i class="fa-solid ${badgeIcon}"></i> Slide #${item.slide} • ${badgeLabel}
                        </div>
                        <div class="slide-size-badge">${sizeText}</div>
                    </div>
                    <div class="slide-body">
                        <div>
                            <div class="slide-title">${item.label}</div>
                            <div class="slide-meta">${item.resolution || (isVideo ? 'Video MP4' : 'Foto JPG')}</div>
                        </div>
                        <button type="button" class="btn-slide-download">
                            <i class="fa-solid fa-download"></i> Unduh ${badgeLabel}
                        </button>
                    </div>
                `;

                const btnDl = card.querySelector('.btn-slide-download');
                btnDl.addEventListener('click', () => {
                    if (isVideo) {
                        if (item.download_url) {
                            downloadDirectFile(item.download_url, `${data.title || 'Video'}-slide-${item.slide}.${item.ext || 'mp4'}`);
                            showToast(`Slide #${item.slide} (Video) siap diunduh!`, 'success');
                        } else {
                            triggerDownload(item.format_id || 'best', `${data.title} - Slide ${item.slide}`, 'video');
                        }
                    } else {
                        downloadDirectFile(item.download_url, `${data.title || 'Foto'}-slide-${item.slide}.jpg`);
                        showToast(`Slide #${item.slide} (Foto) siap diunduh!`, 'success');
                    }
                });

                carouselItemsContainer.appendChild(card);
            });

            // Action "Unduh Semua Slide"
            btnDownloadAll.onclick = async () => {
                showToast(`Memulai pengunduhan ${items.length} slide secara bertahap...`, 'info');
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item.type === 'video') {
                        if (item.download_url) {
                            downloadDirectFile(item.download_url, `${data.title || 'Video'}-slide-${item.slide}.${item.ext || 'mp4'}`, {
                                title: `${data.title || 'Video'} - Slide ${item.slide}`,
                                platform: data.platform,
                                original_url: currentMediaData?.url || '',
                                filesize: item.filesize || 0
                            });
                        } else {
                            await triggerDownload(item.format_id || 'best', `${data.title} - Slide ${item.slide}`, 'video');
                        }
                    } else {
                        downloadDirectFile(item.download_url, `${data.title || 'Foto'}-slide-${item.slide}.jpg`, {
                            title: `${data.title || 'Foto'} - Slide ${item.slide}`,
                            platform: data.platform,
                            original_url: currentMediaData?.url || '',
                            filesize: item.filesize || 0
                        });
                    }
                    await new Promise(r => setTimeout(r, 700));
                }
                showToast('Semua slide telah dikirim ke antrean pengunduhan!', 'success');
            };

            // Otomatis aktifkan tab Carousel
            tabCarouselBtn.click();
        } else {
            if (tabCarouselBtn) tabCarouselBtn.classList.add('hidden');
            if (videos.length === 0 && images.length > 0) {
                tabGalleryBtn.click();
            } else {
                const videoTab = document.querySelector('.tab-btn[data-tab="video"]');
                if (videoTab) videoTab.click();
            }
        }

        resultCard.classList.remove('hidden');
        resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // -------------------------------------------------------------
    // Trigger Download API
    // -------------------------------------------------------------
    async function triggerDownload(formatId, title, type) {
        if (!currentMediaData) return;

        downloadProgressBox.classList.remove('hidden');
        downloadStatusText.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Memproses pengunduhan ${type}... Harap tunggu sebentar.`;

        try {
            const res = await fetch('api/download', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    url: currentMediaData.url,
                    format_id: formatId,
                    title: currentMediaData.title,
                    platform: currentMediaData.platform,
                    thumbnail: currentMediaData.thumbnail
                })
            });

            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'Gagal mengunduh');
            }

            downloadStatusText.innerHTML = `<i class="fa-solid fa-circle-check" style="color: var(--success);"></i> Unduhan selesai (${data.data.filesize_fmt})! File siap disimpan.`;
            showToast(`File ${data.data.filename} siap diunduh!`, 'success');

            // Otomatis download file ke browser user
            const downloadLink = document.createElement('a');
            downloadLink.href = data.data.download_url;
            downloadLink.download = data.data.filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);

            // Muat ulang riwayat
            loadHistory();
        } catch (err) {
            downloadStatusText.innerHTML = `<i class="fa-solid fa-circle-xmark" style="color: var(--danger);"></i> Error: ${err.message}`;
            showToast(err.message, 'error');
        }
    }

    // Ekspos ke window agar tombol inline bisa memanggil jika perlu
    window.triggerDownload = triggerDownload;

    // -------------------------------------------------------------
    // Tab Switching
    // -------------------------------------------------------------
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;

            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetPanel = document.getElementById(`tab-panel-${tabName}`);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });



    // -------------------------------------------------------------
    // Status Check
    // -------------------------------------------------------------
    async function checkSystemStatus() {
        try {
            const res = await fetch('api/status');
            const data = await res.json();
            const statusText = document.getElementById('status-text');
            if (data.status === 'active') {
                statusText.textContent = `Engine Siap (${data.engine_version.split('\n')[0] || 'yt-dlp'})`;
            }
        } catch (err) {
            console.warn('Status check warning:', err);
        }
    }

    // -------------------------------------------------------------
    // Direct File Download via Server Proxy (Bypass CORS & Force Save)
    // -------------------------------------------------------------
    function downloadDirectFile(url, filename, meta = {}) {
        if (!url) return;
        let proxyUrl = `api/proxy-download?url=${encodeURIComponent(url)}&name=${encodeURIComponent(filename || 'unduhan.mp4')}`;
        if (meta.title) proxyUrl += `&title=${encodeURIComponent(meta.title)}`;
        if (meta.platform) proxyUrl += `&platform=${encodeURIComponent(meta.platform)}`;
        if (meta.original_url) proxyUrl += `&original_url=${encodeURIComponent(meta.original_url)}`;
        if (meta.filesize) proxyUrl += `&filesize=${encodeURIComponent(meta.filesize)}`;

        const a = document.createElement('a');
        a.href = proxyUrl;
        a.download = filename || 'unduhan.mp4';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // -------------------------------------------------------------
    // Helper Functions
    // -------------------------------------------------------------
    function setLoading(isLoading) {
        btnAnalyze.disabled = isLoading;
        if (isLoading) {
            btnAnalyze.querySelector('.btn-content').classList.add('hidden');
            analyzeSpinner.classList.remove('hidden');
        } else {
            btnAnalyze.querySelector('.btn-content').classList.remove('hidden');
            analyzeSpinner.classList.add('hidden');
        }
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    function formatDuration(sec) {
        if (!sec) return '00:00';
        const m = Math.floor(sec / 60);
        const s = Math.floor(sec % 60);
        return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }

    function formatBytes(bytes) {
        if (bytes <= 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
});
