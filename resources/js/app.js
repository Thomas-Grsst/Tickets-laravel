import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT),
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

const REFRESH_COALESCING_DELAY = 150;

const openTicketChannels = new Map();

let pendingRefresh = null;

/**
 * Both ticket events land on the same channel and mean the same thing to a list — this row
 * is stale — so an assignment, which fires both, still costs a single round trip.
 */
const scheduleRefresh = (refresh) => {
    window.clearTimeout(pendingRefresh);
    pendingRefresh = window.setTimeout(refresh, REFRESH_COALESCING_DELAY);
};

/**
 * Brings the open subscriptions in line with the ticket ids currently on the page: the rows
 * that left are dropped, the ones that arrived are subscribed, and the untouched ones keep
 * their socket. Every channel is private, so each subscription is authorised server-side
 * before a single message is delivered.
 */
window.synchronizeTicketChannels = (ticketIds, refresh) => {
    const wantedTicketIds = new Set((ticketIds ?? []).map(Number));

    openTicketChannels.forEach((_, ticketId) => {
        if (wantedTicketIds.has(ticketId)) {
            return;
        }

        window.Echo.leave(`tickets.${ticketId}`);
        openTicketChannels.delete(ticketId);
    });

    wantedTicketIds.forEach((ticketId) => {
        if (openTicketChannels.has(ticketId)) {
            return;
        }

        openTicketChannels.set(
            ticketId,
            window.Echo.private(`tickets.${ticketId}`)
                .listen('.ticket.assigned', () => scheduleRefresh(refresh))
                .listen('.ticket.status-changed', () => scheduleRefresh(refresh)),
        );
    });
};
