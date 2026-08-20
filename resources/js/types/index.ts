import { PageProps as InertiaPageProps } from '@inertiajs/core';
// ─── Organization ───
export interface Organization {
    id: string;
    name: string;
    slug: string;
    organization_type: string;
    parent_id: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

// ─── User ───
export interface User {
    id: string;
    uuid: string;
    name: string;
    username: string;
    email: string;
    organization_id: string;
    participant_id: string | null;
    organization?: Organization;
    participant?: Participant;
    roles?: Role[];
    sports?: Sport[];
    created_at: string;
    updated_at: string;
    is_active?: boolean;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
}

// ─── Sport ───
export interface Sport {
    id: string;
    organization_id: string;
    name: string;
    slug: string;
    icon: string | null;
    is_active: boolean;
    categories?: SportCategory[];
    created_at: string;
    updated_at: string;
}

// ─── SportCategory ───
export interface SportCategory {
    id: string;
    organization_id: string;
    sport_id: string;
    name: string;
    slug: string;
    quota_mode: 'gender_based' | 'open_total' | 'mixed_total';
    max_athletes_total: number | null;
    is_active: boolean;
    max_male_athletes: number | null;
    max_female_athletes: number | null;
    min_male_athletes: number | null;
    min_female_athletes: number | null;
    max_officials: number | null;
    allowed_roles?: ('athlete_male' | 'athlete_female')[];
    created_at: string;
    updated_at: string;
    sport?: Sport;
}

// ─── Session ───
export interface Session {
    id: string;
    organization_id: string;
    name: string;
    slug: string;
    description: string | null;
    start_date: string;
    end_date: string;
    is_active: boolean;
    ranking_strategy: string | null;
    ranking_rules?: RankingRules | null;
    created_at: string;
    updated_at: string;
}

// ─── Tournament ───
export interface Tournament {
    id: string;
    organization_id: string;
    session_id: string;
    name: string;
    slug: string;
    description: string | null;
    start_date: string;
    end_date: string;
    is_active: boolean;
    ranking_strategy: string | null;
    ranking_rules?: RankingRules | null;
    session?: Session;
    sports?: { id: string; name: string }[];
    created_at: string;
    updated_at: string;
}

export interface RankingRules {
    points?: {
        win_points: number;
        draw_points: number;
        loss_points: number;
        tiebreakers: string[];
    };
    win_rate?: { tiebreakers: string[] };
    medal_tally?: { tiebreakers: string[] };
}

// ─── Event ───
export interface Event {
    id: string;
    organization_id: string;
    tournament_id: string;
    sport_id: string;
    sport_category_id: string;
    name: string;
    slug: string;
    description: string | null;
    venues: string[] | null;
    start_date: string;
    end_date: string;
    registration_deadline: string | null;
    is_active: boolean;
    tournament?: Tournament;
    sport?: Sport;
    sport_category?: SportCategory;
    pools_count?: number;
    matches_count?: number;
    completed_matches_count?: number;
    registrations_count?: number;
    confirmed_participants_count?: number;
    pending_participants_count?: number;
    participants_count?: number;
    format?: string | null;
    pool_size?: number | null;
    created_at: string;
    updated_at: string;
}

// ─── Pool ───
export interface Pool {
    id: string;
    organization_id: string;
    event_id: string;
    name: string;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

// ─── Participant ───
export interface Participant {
    id: string;
    organization_id: string;
    session_id: string | null;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    participant_type: 'individual' | 'team';
    team_name: string | null;
    status: string;
    notes: string | null;
    logo_path: string | null;
    logo_url: string | null;
    inverse_logo_path: string | null;
    inverse_logo_url: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;  // soft deletes
    organization?: Organization;
    users?: { uuid: string; name: string; email: string; roles?: { id: number; name: string }[] }[];
}

// ─── Registration ───
export interface Registration {
    id: string;
    organization_id: string;
    tournament_id: string;
    participant_id: string;
    status: string;
    registered_at: string;
    notes: string | null;
    tournament?: Tournament;
    participant?: Participant;
    created_at: string;
    updated_at: string;
}

// ─── Fixture (Match) ───
export interface Fixture {
    id: string;
    organization_id: string;
    event_id: string;
    pool_id: string | null;
    stage: 'semi_final' | 'bronze' | 'final' | null;
    round: number | null;
    match_number: number;
    home_participant_id: string | null;
    away_participant_id: string | null;
    venue: string | null;
    scheduled_at: string | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    notes: string | null;
    event?: Event;
    pool?: { id: string; name: string };
    home_participant?: Participant;
    away_participant?: Participant;
    result?: Result;
    created_at: string;
    updated_at: string;
}

// ─── Result ───
export interface Result {
    id: string;
    organization_id: string;
    match_id: string;
    score_home: number | null;
    score_away: number | null;
    winner_participant_id: string | null;
    notes: string | null;
    match?: Fixture;
    winner?: Participant;
    created_at: string;
    updated_at: string;
}

// ─── Ranking ───
export interface RankingEntry {
    participant_id: string;
    participant_name: string;
    participant_type: string;
    team_name: string | null;
    matches_played: number;
    wins: number;
    draws: number;
    losses: number;
    score_for: number;
    score_against: number;
    goal_difference: number;
    points?: number;
    win_rate?: number;
    gold?: number;
    silver?: number;
    bronze?: number;
    total_medals?: number;
    rank: number;
}

// ─── EventParticipant ───
export interface EventParticipant {
    id: string;
    event_id: string;
    participant_id: string;
    registration_date: string | null;
    status: string;
    seed_number: number | null;
    notes: string | null;
    event?: Event;
    participant?: Participant;
    squad_members?: SquadMember[];
    created_at: string;
    updated_at: string;
}

// ─── SquadMember ───
export interface SquadMember {
    id: string;
    event_participant_id: string;
    organization_id: string;
    name: string;
    matrix_no: string | null;
    role: 'athlete_male' | 'athlete_female' | 'assistant_manager' | 'manager' | 'coach' | 'physio';
    identification_no: string | null;
    phone: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

// ─── Pagination ───
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

// ─── Flash ───
export interface Flash {
    success?: string;
    error?: string;
}

// ─── Auth ───
export interface Auth {
    user: User;
}

// ─── Notification ───
export interface NotificationItem {
    id: string;
    data: {
        message?: string;
        type?: string;
        event_name?: string;
        faculty_name?: string;
        event_participant_id?: string;
    };
    read_at: string | null;
    created_at: string;
}

// ─── Page Props (extended from Inertia) ───
export interface PageProps extends InertiaPageProps {
    auth: Auth;
    flash: Flash;
    locale?: string;
    locales?: Array<{
        code: string;
        label: string;
    }>;
    app?: {
        name: string;
    };
    isSuperAdmin?: boolean;
    isFacultyRep?: boolean;
    isDean?: boolean;
    notification_count?: number;
    notifications?: NotificationItem[];
}
