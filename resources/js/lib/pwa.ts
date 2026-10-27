/** App aperta come PWA installata (schermata Home / finestra standalone). */
export function isStandalone(): boolean {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as { standalone?: boolean }).standalone === true
    );
}

export function isIos(): boolean {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}
