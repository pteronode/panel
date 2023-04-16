import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { ServerBackup } from '@/api/server/types';
import { rawDataToServerBackup } from '@/api/transformers';
import { ServerContext } from '@/state/server';
import { createContext, useContext } from 'react';

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

type BackupResponse = PaginatedResult<ServerBackup> & { snapshotCount: number };

export default () => {
    const { page } = useContext(Context);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<BackupResponse>(['server:snapshots', uuid, page], async () => {
        const { data } = await http.get(`/api/client/servers/${uuid}/snapshots`, { params: { page } });

        return {
            items: (data.data || []).map(rawDataToServerBackup),
            pagination: getPaginationSet(data.meta.pagination),
            snapshotCount: data.meta.snapshot_count,
        };
    });
};
