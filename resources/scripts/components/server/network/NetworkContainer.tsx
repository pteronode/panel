import React from 'react';
import Spinner from '@/components/elements/Spinner';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';
import AllocationRow from '@/components/server/network/AllocationRow';

const NetworkContainer = () => {
    const server = ServerContext.useStoreState((state) => state.server.data!);
    const additional_ports = ServerContext.useStoreState((state) => state.server.data!.additional_ports);

    return (
        <ServerContentBlock showFlashKey={'server:network'} title={'Network'}>
            {!additional_ports ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    <AllocationRow isDefault={true} ip={String(server.service.ip)} port={String(server.default_port)} />

                    {additional_ports.map((port, key) => (
                        <AllocationRow isDefault={false} ip={String(server.service.ip)} port={port} key={key} />
                    ))}
                </>
            )}
        </ServerContentBlock>
    );
};

export default NetworkContainer;
