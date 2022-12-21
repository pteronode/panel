import React, { memo, useCallback, useState } from 'react';
import isEqual from 'react-fast-compare';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import InputSpinner from '@/components/elements/InputSpinner';
import { Textarea } from '@/components/elements/Input';
import Can from '@/components/elements/Can';
import { Button } from '@/components/elements/button/index';
import GreyRowBox from '@/components/elements/GreyRowBox';
import styled from 'styled-components/macro';
import { ServerContext } from '@/state/server';
import CopyOnClick from '@/components/elements/CopyOnClick';
import DeleteAllocationButton from '@/components/server/network/DeleteAllocationButton';
import setPrimaryServerAllocation from '@/api/server/network/setPrimaryServerAllocation';
import Code from '@/components/elements/Code';

const Label = styled.label`
    ${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}
`;

interface Props {
    isDefault: boolean;
    port: string;
}

const AllocationRow = ({ isDefault, port }: Props) => {
    const [loading, setLoading] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const setPrimaryAllocation = () => {
        setPrimaryServerAllocation(uuid, 1).catch((error) => {
            //
        });
    };

    return (
        <GreyRowBox $hoverable={false} className={'flex-wrap md:flex-nowrap mt-2'}>
            <div className={'flex items-center w-full md:w-auto'}>
                <div className={'pl-4 pr-6 text-neutral-400'}>
                    <FontAwesomeIcon icon={faNetworkWired} />
                </div>
                <div className={'mr-4 flex-1 md:w-40'}>
                    <CopyOnClick text={'192.0.2.1'}>
                        <Code dark>{'192.0.2.1'}</Code>
                    </CopyOnClick>
                    <Label>{!'description' ? 'Hostname' : 'IP Address'}</Label>
                </div>
                <div className={'w-16 md:w-24 overflow-hidden'}>
                    <Code dark>{port}</Code>
                    <Label>Port</Label>
                </div>
            </div>
            <div className={'mt-4 w-full md:mt-0 md:flex-1 md:w-auto'}>
                <InputSpinner visible={loading}>
                    <Textarea
                        className={'bg-neutral-800 hover:border-neutral-600 border-transparent'}
                        placeholder={'Notes'}
                        defaultValue={undefined}
                    />
                </InputSpinner>
            </div>
            <div className={'flex justify-end space-x-4 mt-4 w-full md:mt-0 md:w-48'}>
                {isDefault ? (
                    <Button size={Button.Sizes.Small} className={'!text-gray-50 !bg-blue-600'} disabled>
                        Primary
                    </Button>
                ) : (
                    <>
                        <Can action={'allocation.delete'}>
                            <DeleteAllocationButton allocation={1} />
                        </Can>
                        <Can action={'allocation.update'}>
                            <Button.Text size={Button.Sizes.Small} onClick={setPrimaryAllocation}>
                                Make Primary
                            </Button.Text>
                        </Can>
                    </>
                )}
            </div>
        </GreyRowBox>
    );
};

export default memo(AllocationRow, isEqual);
