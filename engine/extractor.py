#!/usr/bin/env python3
"""
extractor.py - Engine Media Extractor & Downloader untuk Unduhanku (Monorepo)
Menggunakan yt-dlp untuk mengekstrak informasi dan mengunduh media dari 1000+ platform.
"""

import sys
import os
import json
import traceback
import warnings
import re
import urllib.request
import urllib.parse
import ssl
import html
from concurrent.futures import ThreadPoolExecutor

# Abaikan warning python agar output stdout tetap valid JSON murni
warnings.filterwarnings("ignore")
os.environ['PYTHONWARNINGS'] = 'ignore'

try:
    import yt_dlp
    from yt_dlp.extractor.instagram import InstagramIE
    from yt_dlp.extractor.twitter import TwitterIE
    from yt_dlp.utils import update_url_query, unified_timestamp, str_or_none, int_or_none, truncate_string, format_field

    # Patch 1: InstagramIE._extract_product untuk single photo post
    _orig_extract_product = InstagramIE._extract_product
    def _patched_extract_product(self, product_info, *args, **kwargs):
        res = _orig_extract_product(self, product_info, *args, **kwargs)
        if not res.get('formats') and res.get('thumbnails'):
            best_thumb = res['thumbnails'][-1]
            thumb_url = best_thumb.get('url')
            res['formats'] = [{
                'format_id': 'original_image',
                'url': thumb_url,
                'ext': 'jpg',
                'vcodec': 'none',
                'acodec': 'none',
                'filesize': 350000,
                'format_note': 'Foto Asli Instagram'
            }]
            if not res.get('url'):
                res['url'] = thumb_url
        return res
    InstagramIE._extract_product = _patched_extract_product

    # Patch 2: InstagramIE._extract_product_media untuk carousel media items (foto & video)
    _orig_extract_product_media = InstagramIE._extract_product_media
    def _patched_extract_product_media(self, product_media):
        res = _orig_extract_product_media(self, product_media)
        thumbs = res.get('thumbnails') or []
        best_thumb_url = thumbs[-1]['url'] if thumbs else None
        
        if not res.get('formats'):
            if best_thumb_url:
                res['formats'] = [{
                    'format_id': 'original_image',
                    'url': best_thumb_url,
                    'ext': 'jpg',
                    'vcodec': 'none',
                    'acodec': 'none',
                    'filesize': 350000,
                    'format_note': 'Foto Asli Instagram'
                }]
        if best_thumb_url and not res.get('thumbnail'):
            res['thumbnail'] = best_thumb_url
        if not res.get('url'):
            if res.get('formats'):
                res['url'] = res['formats'][-1].get('url')
            elif best_thumb_url:
                res['url'] = best_thumb_url
        return res
    InstagramIE._extract_product_media = _patched_extract_product_media

    # Patch 3: TwitterIE._real_extract untuk mendukung X/Twitter dengan multiple image atau mixed media
    _orig_twitter_real_extract = TwitterIE._real_extract
    def _patched_twitter_real_extract(self, url):
        m = self._match_valid_url(url)
        twid = m.group('id')
        status = self._extract_status(twid)
        if not status:
            return _orig_twitter_real_extract(self, url)
        
        media_list = (status.get('extended_entities', {}) or {}).get('media') or (status.get('entities', {}) or {}).get('media') or []
        if not media_list:
            return _orig_twitter_real_extract(self, url)
        
        has_photos = any(med.get('type') == 'photo' for med in media_list)
        has_videos = any(med.get('type') in ('video', 'animated_gif') or 'video_info' in med for med in media_list)

        # Jika tweet HANYA berisi video murni tanpa foto, gunakan extractor bawaan yt-dlp
        if has_videos and not has_photos:
            return _orig_twitter_real_extract(self, url)

        title = (status.get('full_text') or status.get('text') or '').replace('\n', ' ')
        title = truncate_string(re.sub(r'\s+(https?://[^ ]+)', '', title), left=72) or f'Tweet_{twid}'
        user = status.get('user') or {}
        uploader = user.get('name')
        if uploader:
            title = f'{uploader} - {title}'
        uploader_id = user.get('screen_name')

        info = {
            'id': twid,
            'title': title,
            'description': status.get('full_text') or status.get('text') or '',
            'uploader': uploader,
            'timestamp': unified_timestamp(status.get('created_at')),
            'channel_id': str_or_none(status.get('user_id_str')) or str_or_none(user.get('id_str')),
            'uploader_id': uploader_id,
            'uploader_url': format_field(uploader_id, None, 'https://twitter.com/%s'),
            'view_count': int_or_none(status.get('view_count')),
            'like_count': int_or_none(status.get('favorite_count')),
            'repost_count': int_or_none(status.get('retweet_count')),
            'comment_count': int_or_none(status.get('reply_count')),
            'age_limit': 18 if status.get('possibly_sensitive') else 0,
            'extractor': 'Twitter',
            'extractor_key': 'Twitter',
        }

        entries = []
        for idx, med in enumerate(media_list):
            m_type = med.get('type')
            med_id = med.get('id_str') or med.get('id') or f'{twid}_{idx+1}'
            med_url = med.get('media_url_https') or med.get('media_url')
            item_title = f'{title} #{idx+1}' if len(media_list) > 1 else title

            if m_type == 'photo':
                orig_url = update_url_query(med_url, {'name': 'orig'}) if med_url else med_url
                w = (med.get('original_info') or {}).get('width')
                h = (med.get('original_info') or {}).get('height')
                entries.append({
                    **info,
                    'id': med_id,
                    'title': item_title,
                    'vcodec': 'none',
                    'acodec': 'none',
                    'formats': [{
                        'format_id': 'orig_image',
                        'url': orig_url,
                        'ext': 'jpg',
                        'vcodec': 'none',
                        'acodec': 'none',
                        'width': w,
                        'height': h,
                        'filesize': 450000,
                        'format_note': 'Foto Asli Twitter/X'
                    }],
                    'thumbnails': [{'url': orig_url, 'width': w, 'height': h}],
                    'thumbnail': orig_url,
                    'url': orig_url,
                })
            else:
                formats = []
                variants = (med.get('video_info') or {}).get('variants') or []
                for v in variants:
                    v_url = v.get('url')
                    if not v_url:
                        continue
                    v_br = v.get('bitrate') or 0
                    h_match = re.search(r'/(\d+)x(\d+)/', v_url)
                    v_h = int(h_match.group(2)) if h_match else None
                    formats.append({
                        'format_id': f'http-{v_br}' if v_br else 'video',
                        'url': v_url,
                        'ext': 'mp4',
                        'tbr': v_br / 1000 if v_br else None,
                        'height': v_h,
                        'vcodec': 'h264',
                        'acodec': 'aac',
                    })
                orig_thumb = update_url_query(med_url, {'name': 'orig'}) if med_url else med_url
                entries.append({
                    **info,
                    'id': med_id,
                    'title': item_title,
                    'formats': formats,
                    'thumbnails': [{'url': orig_thumb}],
                    'thumbnail': orig_thumb,
                    'url': formats[-1]['url'] if formats else None,
                    'duration': float((med.get('video_info') or {}).get('duration_millis') or 0) / 1000,
                })

        if len(entries) == 1:
            return entries[0]
        return self.playlist_result(entries, playlist_id=twid, playlist_title=title)

    TwitterIE._real_extract = _patched_twitter_real_extract

    # Patch 4: TikTokIE._parse_aweme_video_web untuk mendukung TikTok Photo Mode (Multiple Images)
    from yt_dlp.extractor.tiktok import TikTokIE
    _orig_tiktok_parse_web = TikTokIE._parse_aweme_video_web
    def _patched_tiktok_parse_web(self, aweme_detail, webpage_url, video_id):
        res = _orig_tiktok_parse_web(self, aweme_detail, webpage_url, video_id)
        img_post = aweme_detail.get('imagePost') or {}
        images = img_post.get('images') or []
        if images and len(images) > 0:
            entries = []
            base_title = res.get('title') or f'TikTok_{video_id}'
            for idx, img in enumerate(images):
                img_urls = (img.get('imageURL') or {}).get('urlList') or []
                img_url = img_urls[0] if img_urls else None
                if not img_url:
                    continue
                w = img.get('imageWidth')
                h = img.get('imageHeight')
                entries.append({
                    **res,
                    'id': f'{video_id}_{idx+1}',
                    'title': f'{base_title} #{idx+1}',
                    'formats': [{
                        'format_id': 'orig_image',
                        'url': img_url,
                        'width': w,
                        'height': h,
                        'ext': 'jpg',
                        'vcodec': 'none',
                        'acodec': 'none',
                        'filesize': 450000,
                        'format_note': 'Foto Asli TikTok'
                    }],
                    'thumbnails': [{'url': img_url, 'width': w, 'height': h}],
                    'thumbnail': img_url,
                    'url': img_url,
                    'vcodec': 'none',
                    'acodec': 'none'
                })
            if entries:
                return {
                    **res,
                    '_type': 'playlist',
                    'entries': entries,
                    'playlist_count': len(entries),
                }
        return res
    TikTokIE._parse_aweme_video_web = _patched_tiktok_parse_web
except Exception:
    pass


def normalize_url(url: str) -> str:
    """Normalisasi format URL sosmed khusus seperti TikTok /photo/ -> /video/."""
    url = url.strip()
    # TikTok mode slide foto: ubah /photo/<id> menjadi /video/<id> agar dikenali yt-dlp
    url = re.sub(r'(tiktok\.com(?:/@[^/?#]+)?)/photo/(\d+)', r'\1/video/\2', url)
    return url


def sanitize_filename(name: str) -> str:
    """Bersihkan karakter berbahaya untuk nama file di Windows/Linux."""
    keepcharacters = (' ', '.', '_', '-')
    return "".join(c for c in name if c.isalnum() or c in keepcharacters).rstrip()


def extract_facebook_photos(raw_url: str):
    """
    Ekstraktor khusus Facebook untuk foto tunggal (single image) maupun
    album / postingan banyak foto (multiple photos / carousel).
    """
    url = raw_url.strip()
    ctx = ssl._create_unverified_context()

    # 1. Resolusi URL redirect jika URL adalah link pendek share (share/p, share/v, fb.me)
    resolved_url = url
    req_resolve = urllib.request.Request(url, headers={
        'User-Agent': 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.9',
    })
    try:
        with urllib.request.urlopen(req_resolve, context=ctx, timeout=12) as resp:
            resolved_url = resp.geturl()
    except urllib.error.HTTPError as e:
        if e.code in (301, 302, 303, 307, 308):
            resolved_url = e.headers.get('Location') or url
    except Exception:
        pass

    # Bersihkan parameter tracking Facebook (rdid, share_url, ref, dll)
    clean_url = resolved_url
    clean_url = re.sub(r'[?&](?:rdid|share_url|ref|refid|locale2|__cft__|__tn__)=[^&]+', '', clean_url)
    if '?' in clean_url and clean_url.endswith('?'):
        clean_url = clean_url[:-1]

    # 2. Ambil HTML konten menggunakan facebookexternalhit terlebih dahulu agar mendapatkan seluruh fbid postingan
    html_content = ""
    for bot_ua in [
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'Twitterbot/1.0',
        'WhatsApp/2.21.12.21 A'
    ]:
        try:
            req_content = urllib.request.Request(clean_url, headers={
                'User-Agent': bot_ua,
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': 'en-US,en;q=0.9',
            })
            with urllib.request.urlopen(req_content, context=ctx, timeout=12) as resp:
                body = resp.read().decode('utf-8', errors='ignore')
                if len(body) > 1000 and ('og:image' in body or 'fbcdn.net' in body or 'fbid=' in body):
                    html_content = body
                    break
        except Exception:
            continue

    if not html_content:
        return None

    # 3. Ekstrak Metadata (Title, Description, Uploader)
    title_m = re.search(r'<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']', html_content)
    if not title_m:
        title_m = re.search(r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']', html_content)
    uploader = html.unescape(title_m.group(1)).strip() if title_m else 'Facebook User'

    desc_m = re.search(r'<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']', html_content)
    if not desc_m:
        desc_m = re.search(r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:description["\']', html_content)
    desc = html.unescape(desc_m.group(1)).strip() if desc_m else ''

    title = desc[:70].replace('\n', ' ').strip() if desc else uploader
    if not title:
        title = f"Facebook_Post_{uploader}"

    # 4. Kumpulkan Semua Foto CDN
    # Cari daftar photo ID (fbid) dalam postingan jika album/multiple photos
    pids = []
    seen_pids = set()
    raw_pids = re.findall(r'fbid=(\d+)', html_content)
    raw_pids += re.findall(r'{"__typename":"Photo"[^}]+"id":"(\d+)"', html_content)

    # Filter agar bukan ID uploader
    uploader_id_m = re.search(r'id=(\d+)', clean_url)
    uploader_id = uploader_id_m.group(1) if uploader_id_m else ''

    for pid in raw_pids:
        if pid != uploader_id and pid not in seen_pids:
            seen_pids.add(pid)
            pids.append(pid)

    photos = []
    seen_bases = set()

    def fetch_single_fb_photo(pid):
        p_url = f"https://web.facebook.com/photo.php?fbid={pid}"
        for b_ua in [
            'Twitterbot/1.0',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'
        ]:
            req_p = urllib.request.Request(p_url, headers={
                'User-Agent': b_ua,
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            })
            try:
                with urllib.request.urlopen(req_p, context=ctx, timeout=12) as r:
                    doc = r.read().decode('utf-8', errors='ignore')
                    m = re.search(r'<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']', doc)
                    if not m:
                        m = re.search(r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']', doc)
                    if m:
                        raw_cdn = html.unescape(m.group(1)).strip()
                        if 'fbcdn.net' in raw_cdn and not any(bad in raw_cdn for bad in ['rsrc.php', 'emoji.php', '.js', '.css', '-1/']):
                            return raw_cdn
            except Exception:
                continue
        return None

    # Jika terdeteksi multiple photo IDs (> 1)
    if len(pids) > 1:
        with ThreadPoolExecutor(max_workers=min(len(pids), 8)) as executor:
            fetched_urls = list(executor.map(fetch_single_fb_photo, pids))
        for f_url in fetched_urls:
            if f_url:
                base = f_url.split('?')[0]
                if base not in seen_bases:
                    seen_bases.add(base)
                    photos.append(f_url)

    # Jika foto tunggal atau resolusi album belum lengkap
    if not photos:
        og_img_m = re.search(r'<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']', html_content)
        if not og_img_m:
            og_img_m = re.search(r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']', html_content)
        og_image = html.unescape(og_img_m.group(1)).strip() if og_img_m else ''

        tw_img_m = re.search(r'<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']', html_content)
        tw_image = html.unescape(tw_img_m.group(1)).strip() if tw_img_m else ''

        for cand in [og_image, tw_image]:
            if cand and ('fbcdn.net' in cand) and not any(bad in cand for bad in ['rsrc.php', 'emoji.php', '.js', '.css', '-1/']):
                base = cand.split('?')[0]
                if base not in seen_bases:
                    seen_bases.add(base)
                    photos.append(cand)

        # Cari foto lain di dalam body HTML
        scontent_matches = re.findall(r'https:[\\/]+[a-zA-Z0-9\.\-]+(?:fbcdn\.net)[^"\'\s<>{}\)]+', html_content)
        for raw_m in scontent_matches:
            clean = html.unescape(raw_m.replace('\\/', '/'))
            clean = re.sub(r'["\'\\].*$', '', clean)
            if any(bad in clean for bad in ['emoji.php', 'rsrc.php', '.js', '.css', '.ico', '-1/', 'cross/']):
                continue
            if any(pt in clean for pt in ['t39.', 't1.', 't31.', 't6/', 'v/t']) or any(ext in clean.lower() for ext in ['.jpg', '.jpeg', '.png', '.webp']):
                base = clean.split('?')[0]
                if base not in seen_bases:
                    seen_bases.add(base)
                    photos.append(clean)

        # Fallback terakhir jika og:image adalah lookaside link
        if not photos and pids:
            first_cdn = fetch_single_fb_photo(pids[0])
            if first_cdn:
                photos.append(first_cdn)

    if not photos:
        return None

    # Buat format items & images
    thumbnail = photos[0]
    items = []
    images = []

    for idx, p_url in enumerate(photos):
        slide_no = idx + 1
        item_id = f"fb_photo_{slide_no}"
        label = f"Slide #{slide_no} • Foto HD" if len(photos) > 1 else "Foto Facebook HD"

        items.append({
            "slide": slide_no,
            "type": "image",
            "id": item_id,
            "label": label,
            "resolution": "Foto Full HD",
            "thumbnail": p_url,
            "download_url": p_url,
            "format_id": "original_image",
            "filesize": 450000,
            "is_estimated": True,
            "ext": "jpg"
        })

        images.append({
            "url": p_url,
            "filesize": 450000,
            "is_estimated": True,
            "label": label
        })

    is_carousel = (len(photos) > 1)

    return {
        "success": True,
        "data": {
            "id": re.search(r'\d+', clean_url).group(0) if re.search(r'\d+', clean_url) else "facebook_media",
            "title": title,
            "url": raw_url,
            "platform": "Facebook",
            "uploader": uploader,
            "duration": 0,
            "thumbnail": thumbnail,
            "view_count": 0,
            "description": desc[:300],
            "is_carousel": is_carousel,
            "items": items,
            "formats": {
                "videos": [],
                "best_audio": None,
                "images": images
            }
        }
    }


def extract_info(raw_url: str):
    """Ekstrak metadata media tanpa mengunduh kontennya."""
    url = normalize_url(raw_url)
    is_fb = bool(re.search(r'(?:facebook\.com|fb\.watch)', url, re.IGNORECASE))
    is_fb_photo = is_fb and any(p in url.lower() for p in ['/photo', '/photos', 'share/p/', 'permalink.php'])

    # Jika secara spesifik merupakan URL foto/postingan gambar Facebook, dahulukan ekstraktor foto FB
    if is_fb_photo:
        fb_res = extract_facebook_photos(url)
        if fb_res and fb_res.get('success'):
            return fb_res

    ydl_opts = {
        'quiet': True,
        'no_warnings': True,
        'skip_download': True,
        'extract_flat': False,
        'ignoreerrors': True,
        'check_formats': False,
        'format': 'all/best/bestvideo/bestaudio',
    }

    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        try:
            info = ydl.extract_info(url, download=False)
        except Exception as e:
            if is_fb:
                fb_res = extract_facebook_photos(url)
                if fb_res and fb_res.get('success'):
                    return fb_res
            return {
                "success": False,
                "error": str(e)
            }

        if not info:
            if is_fb:
                fb_res = extract_facebook_photos(url)
                if fb_res and fb_res.get('success'):
                    return fb_res
            return {"success": False, "error": "Media tidak ditemukan atau link tidak valid"}

        # CRITICAL FIX: Convert info['entries'] generator to a permanent list immediately!
        # Iterating over info['entries'] once exhausts the generator, causing carousel items to be lost.
        raw_entries = []
        if info.get('entries') is not None:
            try:
                raw_entries = [e for e in list(info.get('entries')) if e]
            except Exception:
                raw_entries = []
            info['entries'] = raw_entries

        # Platform / Extractor
        platform = info.get('extractor_key') or info.get('extractor') or 'Unknown'
        title = info.get('title') or 'Media_Unduhan'
        thumbnail = info.get('thumbnail') or ''
        duration = info.get('duration') or 0
        uploader = info.get('uploader') or info.get('channel') or 'Unknown'
        view_count = info.get('view_count') or 0

        if not thumbnail and raw_entries:
            first_e = raw_entries[0]
            thumbnail = first_e.get('thumbnail') or (first_e.get('thumbnails') or [{}])[-1].get('url') or first_e.get('url') or ''

        # Kumpulkan opsi format yang ramah pengguna
        formats = []
        raw_formats = info.get('formats') or []

        # 1. Video formats
        seen_res = set()
        for f in raw_formats:
            vcodec = f.get('vcodec', 'none')
            acodec = f.get('acodec', 'none')
            height = f.get('height')
            ext = f.get('ext', 'mp4')
            format_id = f.get('format_id')
            filesize = f.get('filesize') or f.get('filesize_approx') or 0

            # Jika platform tidak menyertakan filesize, hitung estimasi dari bitrate * durasi
            is_estimated = False
            if not filesize and duration:
                bitrate = f.get('tbr') or ((f.get('vbr') or 0) + (f.get('abr') or 0))
                if bitrate:
                    # Rumus: (kbps * 1024 * detik) / 8 = byte
                    filesize = int((bitrate * 1024 * duration) / 8)
                    is_estimated = True

            # Filter video dengan resolusi valid
            if vcodec != 'none' and height and height not in seen_res:
                seen_res.add(height)
                ext = f.get('ext', 'mp4')
                format_id = f.get('format_id')
                is_combined = (acodec != 'none')
                
                formats.append({
                    "id": format_id,
                    "type": "video",
                    "resolution": f"{height}p",
                    "height": height,
                    "ext": ext,
                    "filesize": filesize,
                    "is_estimated": is_estimated,
                    "label": f"{height}p ({ext.upper()})",
                    "has_audio": is_combined,
                    "download_url": f.get('url') if is_combined else None
                })

        # Urutkan format video dari resolusi tertinggi ke terendah
        video_formats = sorted(
            [f for f in formats if f["type"] == "video"],
            key=lambda x: x.get("height", 0),
            reverse=True
        )

        # 2. Audio format (Best Audio)
        audio_formats = []
        best_audio = None
        for f in raw_formats:
            if f.get('vcodec') == 'none' and f.get('acodec') != 'none':
                abr = f.get('abr') or 128
                filesize = f.get('filesize') or f.get('filesize_approx') or 0
                is_estimated = False
                if not filesize and duration and abr:
                    filesize = int((abr * 1024 * duration) / 8)
                    is_estimated = True

                audio_formats.append({
                    "id": f.get('format_id'),
                    "type": "audio",
                    "bitrate": f"{int(abr)} kbps",
                    "ext": "mp3",
                    "filesize": filesize,
                    "is_estimated": is_estimated,
                    "label": f"Audio MP3 ({int(abr)} kbps)",
                    "download_url": f.get('url')
                })
        
        # Ambil audio terbaik jika ada
        if audio_formats:
            best_audio = sorted(audio_formats, key=lambda x: x.get("filesize", 0), reverse=True)[0]
        elif duration:
            # Fallback jika stream audio tidak terpisah, hitung estimasi 128 kbps
            est_audio_size = int((128 * 1024 * duration) / 8)
            best_audio = {
                "id": "best_audio",
                "type": "audio",
                "bitrate": "128 kbps",
                "ext": "mp3",
                "filesize": est_audio_size,
                "is_estimated": True,
                "label": "Audio MP3 (128 kbps)",
                "download_url": None
            }

        # 3. Susun Itemized Carousel (Slide Cards) untuk mixed media & multiple media
        items = []
        if raw_entries and len(raw_entries) > 0:
            for idx, entry in enumerate(raw_entries):
                if not entry:
                    continue
                vcodec = entry.get('vcodec', 'none')
                entry_formats = entry.get('formats') or []
                has_video = (vcodec != 'none') or any(f.get('vcodec') != 'none' for f in entry_formats)
                thumbs = entry.get('thumbnails') or []
                thumb_url = thumbs[-1].get('url') if thumbs else (entry.get('thumbnail') or entry.get('url') or '')
                
                if has_video:
                    v_formats = [f for f in entry_formats if f.get('vcodec') != 'none']
                    best_v = v_formats[-1] if v_formats else (entry_formats[-1] if entry_formats else None)
                    height = best_v.get('height') if best_v else None
                    res_label = f"{height}p" if height else "Video HD"
                    v_size = (best_v.get('filesize') or best_v.get('filesize_approx') or 
                              int((1000 * 1024 * (entry.get('duration') or 10)) / 8)) if best_v else 2000000
                    
                    v_dl_url = (best_v.get('url') if best_v else None) or entry.get('url')
                    
                    items.append({
                        "slide": len(items) + 1,
                        "type": "video",
                        "id": entry.get('id') or f"video_{len(items) + 1}",
                        "label": f"Slide #{len(items) + 1} • Video ({res_label})",
                        "resolution": res_label,
                        "thumbnail": thumb_url,
                        "download_url": v_dl_url,
                        "format_id": best_v.get('format_id') if best_v else 'best',
                        "filesize": v_size,
                        "is_estimated": True,
                        "ext": best_v.get('ext', 'mp4') if best_v else 'mp4'
                    })
                else:
                    # Foto / Image
                    img_dl_url = (entry_formats[0].get('url') if (entry_formats and entry_formats[0].get('url')) else None) or thumb_url or entry.get('url')
                    if img_dl_url:
                        items.append({
                            "slide": len(items) + 1,
                            "type": "image",
                            "id": entry.get('id') or f"img_{len(items) + 1}",
                            "label": f"Slide #{len(items) + 1} • Foto HD",
                            "resolution": "Foto Full HD",
                            "thumbnail": thumb_url or img_dl_url,
                            "download_url": img_dl_url,
                            "format_id": "image",
                            "filesize": entry.get('filesize') or 450000,
                            "is_estimated": True,
                            "ext": "jpg"
                        })

        # 4. Kumpulkan Images
        images = []
        if items:
            for it in items:
                if it['type'] == 'image':
                    images.append({
                        "url": it['download_url'],
                        "filesize": it['filesize'],
                        "is_estimated": True,
                        "label": it['label']
                    })
        elif info.get('thumbnails'):
            valid_thumbs = [t for t in info.get('thumbnails', []) if t.get('url')]
            if valid_thumbs:
                best_t = valid_thumbs[-1]
                images.append({
                    "url": best_t.get('url'),
                    "filesize": best_t.get('filesize') or 350000,
                    "is_estimated": True,
                    "label": "Foto Resolusi Penuh"
                })
        elif thumbnail:
            images.append({
                "url": thumbnail,
                "filesize": 350000,  # estimasi cover HD ~350 KB
                "is_estimated": True,
                "label": "Thumbnail / Gambar Utama"
            })

        # Jika video_formats kosong (biasa terjadi pada playlist/carousel Instagram & Twitter), isi dari video slides di items
        if not video_formats and items:
            for it in items:
                if it['type'] == 'video' and it.get('download_url'):
                    video_formats.append({
                        "id": it.get('format_id') or 'best',
                        "type": "video",
                        "resolution": it.get('resolution') or 'HD',
                        "height": 720,
                        "ext": it.get('ext') or 'mp4',
                        "filesize": it.get('filesize', 0),
                        "is_estimated": True,
                        "label": it.get('label') or 'Video',
                        "has_audio": True,
                        "download_url": it.get('download_url')
                    })

        is_carousel = (len(items) > 1)

        return {
            "success": True,
            "data": {
                "id": info.get('id'),
                "title": title,
                "url": url,
                "platform": platform,
                "uploader": uploader,
                "duration": duration,
                "thumbnail": thumbnail,
                "view_count": view_count,
                "description": (info.get('description') or '')[:300],
                "is_carousel": is_carousel,
                "items": items,
                "formats": {
                    "videos": video_formats[:6],  # batasi 6 opsi kualitas teratas
                    "best_audio": best_audio,
                    "images": images
                }
            }
        }


import shutil

def get_ffmpeg_path():
    """Deteksi lokasi ffmpeg di engine/bin/ atau PATH sistem."""
    internal_bin = os.path.join(os.path.dirname(__file__), 'bin')
    if os.path.exists(os.path.join(internal_bin, 'ffmpeg.exe')) or os.path.exists(os.path.join(internal_bin, 'ffmpeg')):
        return internal_bin
    sys_ffmpeg = shutil.which('ffmpeg')
    if sys_ffmpeg:
        return os.path.dirname(sys_ffmpeg)
    return None


def download_media(raw_url: str, format_id: str, output_dir: str):
    """Unduh media dengan format tertentu ke direktori lokal tanpa crash ketika ffmpeg absen."""
    url = normalize_url(raw_url)
    os.makedirs(output_dir, exist_ok=True)

    is_fb = bool(re.search(r'(?:facebook\.com|fb\.watch)', url, re.IGNORECASE))
    is_fb_photo = is_fb and any(p in url.lower() for p in ['/photo', '/photos', 'share/p/', 'permalink.php'])
    is_image_request = (
        format_id in ('image', 'original_image', 'orig_image') or
        (isinstance(format_id, str) and format_id.startswith('fb_photo_')) or
        ('fbcdn.net' in url.lower()) or
        any(url.lower().split('?')[0].endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.webp'])
    )

    def _download_fb_photo_helper(target_photo_url: str, item_title: str, slide_num: int = 1):
        ext = 'jpg'
        if '.png' in target_photo_url.lower(): ext = 'png'
        elif '.webp' in target_photo_url.lower(): ext = 'webp'

        safe_title = re.sub(r'[\\/*?:"<>|]', '_', item_title)[:60].strip() or "facebook_photo"
        clean_fn = f"{safe_title}_{slide_num}.{ext}" if slide_num > 1 else f"{safe_title}.{ext}"
        target_path = os.path.join(output_dir, clean_fn)

        req = urllib.request.Request(
            target_photo_url,
            headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer': 'https://www.facebook.com/'
            }
        )
        ctx = ssl._create_unverified_context()
        with urllib.request.urlopen(req, context=ctx, timeout=30) as resp, open(target_path, 'wb') as f:
            shutil.copyfileobj(resp, f)

        filesize = os.path.getsize(target_path) if os.path.exists(target_path) else 0
        return {
            "success": True,
            "filename": clean_fn,
            "filepath": target_path,
            "filesize": filesize,
            "title": item_title,
            "ext": ext
        }

    # Jika langsung berupa direct image URL
    if ('fbcdn.net' in url.lower()) or any(url.lower().split('?')[0].endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.webp']):
        try:
            return _download_fb_photo_helper(url, "image_download", 1)
        except Exception as e:
            if not is_fb:
                return {"success": False, "error": str(e)}

    # Jika secara spesifik URL foto Facebook atau format foto diminta
    if is_fb_photo or (is_fb and is_image_request):
        fb_res = extract_facebook_photos(url)
        if fb_res and fb_res.get('success'):
            data = fb_res.get('data', {})
            items = data.get('items', [])
            selected = None
            if isinstance(format_id, str) and format_id.startswith('fb_photo_'):
                for itm in items:
                    if itm.get('id') == format_id:
                        selected = itm
                        break
            if not selected and items:
                selected = items[0]

            if selected and selected.get('download_url'):
                try:
                    return _download_fb_photo_helper(
                        selected['download_url'],
                        data.get('title') or "Facebook Photo",
                        selected.get('slide', 1)
                    )
                except Exception as e:
                    return {"success": False, "error": f"Gagal mengunduh foto Facebook: {str(e)}"}

    # Format download template
    outtmpl = os.path.join(output_dir, '%(title).100s-%(id)s.%(ext)s')

    ffmpeg_dir = get_ffmpeg_path()
    has_ffmpeg = (ffmpeg_dir is not None)

    ydl_opts = {
        'outtmpl': outtmpl,
        'quiet': True,
        'no_warnings': True,
        'noplaylist': False,
    }

    if has_ffmpeg:
        ydl_opts['ffmpeg_location'] = ffmpeg_dir

    if format_id == 'best_audio':
        ydl_opts['format'] = 'bestaudio/best'
        if has_ffmpeg:
            ydl_opts['postprocessors'] = [{
                'key': 'FFmpegExtractAudio',
                'preferredcodec': 'mp3',
                'preferredquality': '192',
            }]
    elif format_id and format_id != 'best':
        if has_ffmpeg:
            # Jika ada ffmpeg, boleh menggabungkan video format pilihan + best audio
            ydl_opts['format'] = f"{format_id}+bestaudio/best"
        else:
            # Tanpa ffmpeg, JANGAN gunakan operator '+' karena akan memicu abort error
            # Prioritas: format_id yang punya audio -> format_id murni -> stream progressive terbaik
            ydl_opts['format'] = f"{format_id}[acodec!=none]/{format_id}/best[acodec!=none]/best"
    else:
        if has_ffmpeg:
            ydl_opts['format'] = 'bestvideo+bestaudio/best'
        else:
            ydl_opts['format'] = 'best[acodec!=none]/best'

    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        try:
            info = ydl.extract_info(url, download=True)
            filename = ydl.prepare_filename(info)
            
            # Jika ada postprocessor mp3
            if format_id == 'best_audio' and not filename.endswith('.mp3'):
                base = os.path.splitext(filename)[0]
                if os.path.exists(base + '.mp3'):
                    filename = base + '.mp3'

            filesize = os.path.getsize(filename) if os.path.exists(filename) else 0

            return {
                "success": True,
                "filename": os.path.basename(filename),
                "filepath": filename,
                "filesize": filesize,
                "title": info.get('title'),
                "ext": os.path.splitext(filename)[1].replace('.', '')
            }
        except Exception as e:
            # Fallback jika Facebook gagal di yt-dlp (misal foto post yang dipanggil tanpa format foto)
            if is_fb:
                fb_res = extract_facebook_photos(url)
                if fb_res and fb_res.get('success'):
                    data = fb_res.get('data', {})
                    items = data.get('items', [])
                    if items and items[0].get('download_url'):
                        try:
                            return _download_fb_photo_helper(
                                items[0]['download_url'],
                                data.get('title') or "Facebook Photo",
                                items[0].get('slide', 1)
                            )
                        except Exception as dl_err:
                            return {"success": False, "error": f"Gagal mengunduh foto Facebook: {str(dl_err)}"}
            return {
                "success": False,
                "error": str(e)
            }


if __name__ == '__main__':
    if hasattr(sys.stdout, 'reconfigure'):
        try:
            sys.stdout.reconfigure(encoding='utf-8')
        except Exception:
            pass

    if len(sys.argv) < 3:
        err_res = {"success": False, "error": "Usage: python extractor.py [info|download] <url> [format_id] [output_dir]"}
        print("__JSON_START__" + json.dumps(err_res, ensure_ascii=True) + "__JSON_END__")
        sys.exit(1)

    action = sys.argv[1].lower()
    target_url = sys.argv[2]

    if action == 'info':
        result = extract_info(target_url)
        print("__JSON_START__" + json.dumps(result, ensure_ascii=True) + "__JSON_END__")
    elif action == 'download':
        f_id = sys.argv[3] if len(sys.argv) > 3 else 'best'
        out_dir = sys.argv[4] if len(sys.argv) > 4 else os.path.join(os.getcwd(), 'storage', 'downloads')
        result = download_media(target_url, f_id, out_dir)
        print("__JSON_START__" + json.dumps(result, ensure_ascii=True) + "__JSON_END__")
    else:
        err_res = {"success": False, "error": f"Aksi tidak dikenal: {action}"}
        print("__JSON_START__" + json.dumps(err_res, ensure_ascii=True) + "__JSON_END__")
