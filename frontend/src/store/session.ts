/**
 * Which sign-in the panel is currently in.
 *
 * The stores that hold a session's data are emptied when it ends, but emptying them says
 * nothing about the requests already in flight. Those answer normally — the token was
 * still valid when they were sent — and their `.then` writes the previous person's
 * watchers, incidents and counts back into the stores a moment after they were cleared,
 * along with a `loaded` flag that stops anything reading them again.
 *
 * So every action that assigns to a store takes the number first and checks it before it
 * writes. Nothing is aborted: an answer nobody wants any more is simply not believed.
 */
let session = 0

export function currentSession(): number {
    return session
}

/** True once the session the caller started in has ended. */
export function sessionEnded(started: number): boolean {
    return started !== session
}

/** Called by the auth store when a session ends, after the stores are cleared. */
export function nextSession(): void {
    session++
}
