(() => {
    'use strict';

    const enabledInput = document.getElementById('adminOrderSoundEnabled');
    const volumeInput = document.getElementById('adminOrderSoundVolume');
    const testButton = document.getElementById('adminOrderSoundTest');
    const statusNode = document.getElementById('adminOrderSoundStatus');
    const stateBadge = document.querySelector('[data-order-sound-state]');
    const buttonLabel = document.querySelector('[data-order-sound-button-label]');
    const buttonIcon = document.querySelector('[data-order-sound-icon]');
    const volumeLabel = document.querySelector('[data-order-sound-volume-label]');

    if (!enabledInput || !volumeInput || !testButton) return;

    const staffScope = document.body.dataset.adminSoundUser || 'admin';
    const enabledKey = `skcoffee.admin.orderSound.enabled.${staffScope}`;
    const volumeKey = `skcoffee.admin.orderSound.volume.${staffScope}`;

    const safeStorageGet = (key) => {
        try { return window.localStorage.getItem(key); } catch (_) { return null; }
    };
    const safeStorageSet = (key, value) => {
        try { window.localStorage.setItem(key, value); } catch (_) { /* Preference remains in memory. */ }
    };

    let enabled = safeStorageGet(enabledKey) === '1';
    const savedVolume = Number(safeStorageGet(volumeKey));
    let volume = Number.isFinite(savedVolume) ? Math.min(1, Math.max(0, savedVolume)) : 0.65;
    let audioContext = null;
    let unavailable = false;

    const setStatus = (message) => {
        if (statusNode) statusNode.textContent = message;
    };

    const syncUi = () => {
        enabledInput.checked = enabled;
        volumeInput.value = String(Math.round(volume * 100));
        if (volumeLabel) volumeLabel.textContent = `${Math.round(volume * 100)}%`;
        if (stateBadge) {
            stateBadge.textContent = enabled ? 'Enabled' : 'Muted';
            stateBadge.className = enabled ? 'badge text-bg-success' : 'badge text-bg-light';
        }
        if (buttonLabel) buttonLabel.textContent = enabled ? 'Sound on' : 'Sound off';
        if (buttonIcon) buttonIcon.className = enabled ? 'bi bi-volume-up' : 'bi bi-volume-mute';
        if (testButton) testButton.disabled = unavailable;
    };

    const getAudioContext = () => {
        if (audioContext) return audioContext;
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            unavailable = true;
            setStatus('This browser does not support notification audio. Visual alerts will continue to work.');
            syncUi();
            return null;
        }
        try {
            audioContext = new AudioContextClass();
            return audioContext;
        } catch (_) {
            unavailable = true;
            setStatus('Audio could not be initialized. Visual alerts will continue to work.');
            syncUi();
            return null;
        }
    };

    const unlockAudio = async () => {
        const context = getAudioContext();
        if (!context) return false;
        try {
            if (context.state !== 'running') await context.resume();
            return context.state === 'running';
        } catch (_) {
            return false;
        }
    };

    const playChime = async (force = false) => {
        if (!force && !enabled) return false;
        const context = getAudioContext();
        if (!context) return false;
        if (context.state !== 'running') {
            const ready = await unlockAudio();
            if (!ready) {
                setStatus('Click Test sound once to allow your browser to play order alerts.');
                return false;
            }
        }

        const startAt = context.currentTime + 0.015;
        const master = context.createGain();
        master.gain.setValueAtTime(Math.max(0.001, volume), startAt);
        master.connect(context.destination);

        // A short, soft three-note chime. One chime is played per batch of new orders.
        const notes = [
            { frequency: 659.25, offset: 0.00 },
            { frequency: 830.61, offset: 0.15 },
            { frequency: 987.77, offset: 0.30 },
        ];

        notes.forEach((note) => {
            const oscillator = context.createOscillator();
            const envelope = context.createGain();
            const start = startAt + note.offset;
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(note.frequency, start);
            envelope.gain.setValueAtTime(0.0001, start);
            envelope.gain.exponentialRampToValueAtTime(0.16, start + 0.025);
            envelope.gain.exponentialRampToValueAtTime(0.0001, start + 0.23);
            oscillator.connect(envelope);
            envelope.connect(master);
            oscillator.start(start);
            oscillator.stop(start + 0.25);
        });

        window.setTimeout(() => {
            try { master.disconnect(); } catch (_) { /* Already disconnected. */ }
        }, 850);
        return true;
    };

    enabledInput.addEventListener('change', async () => {
        enabled = enabledInput.checked;
        safeStorageSet(enabledKey, enabled ? '1' : '0');
        syncUi();
        if (!enabled) {
            setStatus('Sound is muted. Visual order notifications remain enabled.');
            return;
        }
        const ready = await unlockAudio();
        if (ready) {
            setStatus('Sound enabled for new website orders.');
            await playChime();
        } else {
            setStatus('Sound is enabled. Click Test sound once if your browser blocks audio.');
        }
    });

    volumeInput.addEventListener('input', () => {
        volume = Math.min(1, Math.max(0, Number(volumeInput.value) / 100));
        safeStorageSet(volumeKey, String(volume));
        syncUi();
    });

    testButton.addEventListener('click', async () => {
        const played = await playChime(true);
        if (played) setStatus('Sound test played. New website order alerts are ready.');
    });

    // Browsers may suspend audio contexts after a page reload. The first normal
    // user gesture unlocks audio for an already-enabled preference without playing a sound.
    const unlockOnFirstGesture = async () => {
        if (enabled) await unlockAudio();
        document.removeEventListener('pointerdown', unlockOnFirstGesture, true);
        document.removeEventListener('keydown', unlockOnFirstGesture, true);
    };
    document.addEventListener('pointerdown', unlockOnFirstGesture, true);
    document.addEventListener('keydown', unlockOnFirstGesture, true);

    window.CoffeeCornerPlayNewOrderSound = () => playChime(false);
    syncUi();
    if (enabled) {
        setStatus('Sound is enabled. If your browser blocks audio after reload, click Test sound once.');
    } else {
        setStatus('Sound is off. Enable it to hear new website orders.');
    }
})();
