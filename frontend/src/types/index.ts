export interface User {
  id: number;
  name: string;
  email: string;
}

export interface LoginForm {
  email: string;
  password: string;
  remember: boolean;
}

export interface RegisterForm {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  message: string;
  token: string;
  user: User;
}

export interface DashboardSummary {
  completed: number;
  updated: number;
  created: number;
  due_soon: number;
}

export interface DashboardData {
  space: string;
  summary: DashboardSummary;
  priority_breakdown: Record<string, number>;
  status_overview: Record<string, number>;
}

export interface ActivityItem {
  id: number;
  user_name: string;
  field_updated: string;
  task_code: string;
  time_ago: string;
}

export interface ActivityGroup {
  date_label: string;
  activities: ActivityItem[];
}

export interface Space {
  id: number;
  nama: string;
  key: string;
  member_emails: string[];
  tasks_count?: number;
}
export interface SpaceForm {
  nama: string;
  key: string;
  member_emails: string[];
}

export interface Board {
  id: number;
  space_id: number;
  nama: string;
  framework: "scrum" | "kanban";
  member_emails: string[];
}

export interface BoardForm {
  nama: string;
  framework: "scrum" | "kanban";
  member_emails: string[];
}
