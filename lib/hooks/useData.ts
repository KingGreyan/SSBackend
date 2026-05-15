import useSWR from 'swr';

const fetcher = (url: string) => fetch(url).then((res) => res.json());

export function useProfile() {
  const { data, error, isLoading, mutate } = useSWR('/api/profile', fetcher);
  return { profile: data, error, isLoading, mutateProfile: mutate };
}

export function useCategories() {
  const { data, error, isLoading, mutate } = useSWR('/api/categories', fetcher);
  return { categories: data?.data || [], error, isLoading, mutateCategories: mutate };
}

export function useTransactions(params?: { startDate?: string; endDate?: string }) {
  const queryString = params
    ? `?${new URLSearchParams(Object.entries(params).filter(([, v]) => v) as [string, string][]).toString()}`
    : '';
  const { data, error, isLoading, mutate } = useSWR(`/api/transactions${queryString}`, fetcher);
  return { transactions: data?.data || [], error, isLoading, mutateTransactions: mutate };
}

export function useTodos(filter?: { completed?: boolean }) {
  const queryString = filter
    ? `?${new URLSearchParams(Object.entries(filter).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]) as [string, string][]).toString()}`
    : '';
  const { data, error, isLoading, mutate } = useSWR(`/api/todos${queryString}`, fetcher);
  return { todos: data?.data || [], error, isLoading, mutateTodos: mutate };
}
