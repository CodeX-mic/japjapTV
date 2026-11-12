(() => {
    'use strict';

    const players = Array.from(document.querySelectorAll('[data-player]'));
    if (!players.length) {
        return;
    }

    const YT_ORIGIN = 'https://www.youtube.com';
    const YT_NOCOOKIE_ORIGIN = 'https://www.youtube-nocookie.com';
    const HLS_CDN = 'https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js';
    const hlsPromises = new Map();

    const loadHlsLibrary = () => {
        if (window.Hls) {
            return Promise.resolve(window.Hls);
        }
        if (hlsPromises.has('hls')) {
            return hlsPromises.get('hls');
        }

        const promise = new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-hls-loader]');
            if (existing) {
                existing.addEventListener('load', () => resolve(window.Hls));
                existing.addEventListener('error', () => reject(new Error('HLS library failed to load')));
                return;
            }

            const script = document.createElement('script');
            script.src = HLS_CDN;
            script.async = true;
            script.dataset.hlsLoader = 'true';
            script.addEventListener('load', () => {
                if (window.Hls) {
                    resolve(window.Hls);
                } else {
                    reject(new Error('HLS library unavailable'));
                }
            });
            script.addEventListener('error', () => reject(new Error('HLS library failed to load')));
            document.head.appendChild(script);
        });

        hlsPromises.set('hls', promise);
        return promise;
    };

    players.forEach((root) => {
        const type = root.getAttribute('data-source-type') || '';
        const iframe = root.querySelector('[data-player-iframe]');
        const video = root.querySelector('[data-player-video]');
        const loading = root.querySelector('[data-player-loading]');
        const fallback = root.querySelector('[data-player-fallback]');
        const fallbackCopy = fallback ? fallback.querySelector('p') : null;
        const retry = root.querySelector('[data-player-retry]');
        const status = root.querySelector('[data-player-status]');
        const statusText = root.querySelector('[data-player-status-text]');
        const playButton = root.querySelector('[data-player-play]');
        const playIcon = root.querySelector('[data-player-play-icon]');
        const muteButton = root.querySelector('[data-player-mute]');
        const muteIcon = root.querySelector('[data-player-mute-icon]');
        const volume = root.querySelector('[data-player-volume]');
        const fullscreen = root.querySelector('[data-player-fullscreen]');

        let playing = false;
        let muted = false;
        let ready = false;
        let loadingTimer = null;
        let stateTimer = null;
        let hlsInstance = null;
        let lastYouTubeState = null;

        root.classList.add('is-enhanced');
        if (type === 'iframe') {
            root.classList.add('is-iframe');
        }

        const setStatus = (text, state = '') => {
            if (statusText) {
                statusText.textContent = text;
            }
            if (status) {
                status.classList.toggle('is-paused', state === 'paused');
                status.classList.toggle('is-error', state === 'error');
            }
        };

        const setPlaying = (value) => {
            playing = Boolean(value);
            if (playButton) {
                playButton.setAttribute('aria-label', playing ? 'Pause' : 'Play');
            }
            if (playIcon) {
                playIcon.className = playing ? 'fa-solid fa-pause' : 'fa-solid fa-play';
            }
        };

        const setMuted = (value) => {
            muted = Boolean(value);
            if (muteButton) {
                muteButton.setAttribute('aria-label', muted ? 'Unmute' : 'Mute');
            }
            if (muteIcon) {
                muteIcon.className = muted || (volume && Number(volume.value) === 0)
                    ? 'fa-solid fa-volume-xmark'
                    : 'fa-solid fa-volume-high';
            }
        };

        const showLoading = () => {
            if (loading) {
                loading.hidden = false;
            }
        };

        const hideLoading = () => {
            window.clearTimeout(loadingTimer);
            if (loading) {
                loading.hidden = true;
            }
        };

        const hideFallback = () => {
            if (fallback) {
                fallback.hidden = true;
            }
        };

        const showFallback = (message) => {
            hideLoading();
            window.clearTimeout(stateTimer);
            setPlaying(false);
            setStatus('Stream error', 'error');
            if (fallbackCopy && message) {
                fallbackCopy.textContent = message;
            }
            if (fallback) {
                fallback.hidden = false;
            }
            root.classList.add('is-offline');
            if ((type === 'youtube_live' || type === 'youtube_video') && iframe) {
                postYouTubeCommand('pauseVideo');
            }
        };

        const postYouTubeCommand = (func, args = []) => {
            if (!iframe || !iframe.contentWindow) {
                return;
            }
            try {
                iframe.contentWindow.postMessage(JSON.stringify({
                    event: 'command',
                    func,
                    args,
                }), YT_ORIGIN);
            } catch (error) {
                // Cross-origin messaging may fail until the frame is ready.
            }
        };

        const updateVolumeIcon = () => {
            if (!volume) {
                return;
            }
            const value = Number(volume.value);
            if (value === 0) {
                setMuted(true);
            } else if (!muted) {
                setMuted(false);
            }
        };

        const toggleYouTubePlayback = () => {
            if (playing) {
                postYouTubeCommand('pauseVideo');
                setStatus('Pausing…');
            } else {
                postYouTubeCommand('playVideo');
                setStatus('Starting…');
            }
        };

        const toggleVideoPlayback = () => {
            if (!video) {
                return;
            }
            if (video.paused) {
                video.play().catch(() => {
                    setStatus('Tap play to start', 'paused');
                });
            } else {
                video.pause();
            }
        };

        const toggleFullscreen = async () => {
            try {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();
                    return;
                }
                if (root.requestFullscreen) {
                    await root.requestFullscreen();
                } else if (video && video.webkitEnterFullscreen) {
                    video.webkitEnterFullscreen();
                }
            } catch (error) {
                // Browsers can refuse fullscreen outside a direct gesture.
            }
        };

        const updateFullscreenLabel = () => {
            if (!fullscreen) {
                return;
            }
            fullscreen.setAttribute('aria-label', document.fullscreenElement ? 'Exit fullscreen' : 'Enter fullscreen');
            const icon = fullscreen.querySelector('i');
            if (icon) {
                icon.className = document.fullscreenElement ? 'fa-solid fa-compress' : 'fa-solid fa-expand';
            }
        };

        if (playButton) {
            playButton.addEventListener('click', () => {
                if (type === 'hls') {
                    toggleVideoPlayback();
                } else if (type === 'youtube_live' || type === 'youtube_video') {
                    toggleYouTubePlayback();
                }
            });
        }

        if (muteButton) {
            muteButton.addEventListener('click', () => {
                if (type === 'hls' && video) {
                    video.muted = !video.muted;
                    setMuted(video.muted);
                } else if (type === 'youtube_live' || type === 'youtube_video') {
                    postYouTubeCommand(muted ? 'unMute' : 'mute');
                    setMuted(!muted);
                }
            });
        }

        if (volume) {
            volume.addEventListener('input', () => {
                const value = Math.max(0, Math.min(100, Number(volume.value) || 0));
                if (type === 'hls' && video) {
                    video.volume = value / 100;
                    video.muted = value === 0;
                    setMuted(value === 0);
                } else if (type === 'youtube_live' || type === 'youtube_video') {
                    postYouTubeCommand('setVolume', [value]);
                    if (value === 0) {
                        postYouTubeCommand('mute');
                        setMuted(true);
                    } else if (muted) {
                        postYouTubeCommand('unMute');
                        setMuted(false);
                    }
                }
                updateVolumeIcon();
            });
        }

        if (fullscreen) {
            fullscreen.addEventListener('click', toggleFullscreen);
        }
        document.addEventListener('fullscreenchange', updateFullscreenLabel);

        if (retry) {
            retry.addEventListener('click', () => {
                hideFallback();
                root.classList.remove('is-offline');
                setStatus('Reloading…');
                if (type === 'hls') {
                    window.location.reload();
                    return;
                }
                if (iframe) {
                    showLoading();
                    const current = iframe.src;
                    iframe.src = current;
                    loadingTimer = window.setTimeout(hideLoading, 9000);
                }
            });
        }

        if (type === 'youtube_live' || type === 'youtube_video') {
            if (!iframe) {
                return;
            }

            setStatus('Connecting…');
            showLoading();

            const onYouTubeMessage = (event) => {
                if (event.source !== iframe.contentWindow) {
                    return;
                }
                if (event.origin !== YT_ORIGIN && event.origin !== YT_NOCOOKIE_ORIGIN) {
                    return;
                }

                let payload = event.data;
                if (typeof payload === 'string') {
                    try {
                        payload = JSON.parse(payload);
                    } catch (error) {
                        return;
                    }
                }
                if (!payload || typeof payload !== 'object') {
                    return;
                }

                const eventName = payload.event;
                const info = payload.info;

                if (eventName === 'onReady') {
                    ready = true;
                    hideLoading();
                    setStatus('Ready');
                    setPlaying(false);
                    return;
                }

                if (eventName === 'onError') {
                    const code = Number(info);
                    let message = 'The official player reported an error. Open the official channel to watch there.';
                    if (code === 101 || code === 150) {
                        message = 'The broadcaster does not allow this stream to be embedded. Open the official channel to watch.';
                    } else if (code === 100) {
                        message = 'This official stream is unavailable, private, or has ended. Check the official channel for the latest status.';
                    } else if (code === 2) {
                        message = 'The official player configuration was rejected. Reload the page or open the official channel.';
                    } else if (code === 5) {
                        message = 'This browser could not play the official stream. Open the official channel to watch there.';
                    }
                    showFallback(message);
                    return;
                }

                let playerState = null;
                if (eventName === 'onStateChange') {
                    playerState = Number(info);
                } else if (eventName === 'infoDelivery' && info && typeof info === 'object' && info.playerState !== undefined) {
                    playerState = Number(info.playerState);
                }
                if (playerState === null || Number.isNaN(playerState)) {
                    return;
                }
                if (eventName === 'infoDelivery' && lastYouTubeState === playerState) {
                    return;
                }
                lastYouTubeState = playerState;

                if (playerState === 1) {
                    ready = true;
                    hideLoading();
                    hideFallback();
                    setPlaying(true);
                    setStatus('Playing');
                } else if (playerState === 2) {
                    setPlaying(false);
                    setStatus('Paused', 'paused');
                } else if (playerState === 3) {
                    setStatus('Buffering…');
                } else if (playerState === 5) {
                    ready = true;
                    hideLoading();
                    setStatus('Ready');
                } else if (playerState === 0) {
                    setPlaying(false);
                    if (type === 'youtube_live') {
                        showFallback('This official livestream has ended. Check the official channel for the next broadcast.');
                    } else {
                        setStatus('Ended');
                    }
                }
            };

            window.addEventListener('message', onYouTubeMessage);

            iframe.addEventListener('load', () => {
                window.clearTimeout(loadingTimer);
                loadingTimer = window.setTimeout(() => {
                    if (!ready) {
                        hideLoading();
                        setStatus('Tap play');
                    }
                }, 1400);
            });

            loadingTimer = window.setTimeout(() => {
                if (!ready) {
                    hideLoading();
                    setStatus('Tap play');
                }
            }, 11000);
        } else if (type === 'hls') {
            if (!video) {
                return;
            }

            video.controls = false;
            video.volume = 0.75;
            if (volume) {
                volume.value = '75';
            }
            setMuted(false);
            setStatus('Loading stream…');
            showLoading();

            const onVideoReady = () => {
                ready = true;
                hideLoading();
                setStatus(video.paused ? 'Ready' : 'Playing');
                setPlaying(!video.paused);
            };

            video.addEventListener('loadedmetadata', onVideoReady);
            video.addEventListener('canplay', onVideoReady);
            video.addEventListener('playing', () => {
                hideLoading();
                setPlaying(true);
                setStatus('Playing');
            });
            video.addEventListener('pause', () => {
                setPlaying(false);
                setStatus('Paused', 'paused');
            });
            video.addEventListener('waiting', () => setStatus('Buffering…'));
            video.addEventListener('volumechange', () => setMuted(video.muted || video.volume === 0));
            video.addEventListener('error', () => {
                showFallback('The official stream could not be played in this browser. It may be offline or restricted.');
            });

            const src = root.getAttribute('data-hls-src') || '';
            if (!src) {
                showFallback('No authorized HLS source is configured.');
                return;
            }

            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = src;
                video.addEventListener('error', () => {
                    showFallback('The official stream could not be played. Open the official channel to watch there.');
                });
            } else {
                loadHlsLibrary().then((Hls) => {
                    if (Hls && Hls.isSupported()) {
                        hlsInstance = new Hls({ enableWorker: true, lowLatencyMode: false });
                        hlsInstance.loadSource(src);
                        hlsInstance.attachMedia(video);
                        hlsInstance.on(Hls.Events.ERROR, (eventName, data) => {
                            if (data && data.fatal) {
                                if (data.type === Hls.ErrorTypes.NETWORK_ERROR) {
                                    try {
                                        hlsInstance.startLoad();
                                        return;
                                    } catch (error) {
                                        // Fall through to the fallback panel.
                                    }
                                }
                                if (hlsInstance) {
                                    hlsInstance.destroy();
                                }
                                showFallback('The official stream could not be reached. It may be offline or restricted in your region.');
                            }
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = src;
                    } else {
                        showFallback('This browser cannot play the official HLS stream. Open the official channel to watch there.');
                    }
                }).catch(() => {
                    showFallback('The stream player could not be loaded. Check your connection or open the official channel.');
                });
            }

            loadingTimer = window.setTimeout(() => {
                if (!ready) {
                    hideLoading();
                    setStatus('Tap play');
                }
            }, 14000);
        } else if (type === 'iframe') {
            setStatus('Official player', '');
            showLoading();
            if (iframe) {
                iframe.addEventListener('load', () => {
                    hideLoading();
                    setStatus('Official player');
                });
            }
            loadingTimer = window.setTimeout(hideLoading, 10000);
        }
    });
})();
