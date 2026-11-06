const headers = {Accept: 'application/json'}

export async function fetchImports()
{
    const res = await fetch('/api/imports',{headers});
    if(!res.ok)
    {
        throw new Error('Failed to load imports')
    }
    return res.json();
}

export async function fetchImport(id)
{
    const res = await fetch(`/api/imports/${id}`,{headers});
    if(!res.ok)
    {
        throw new Error('Failed to load imports')
    }
    return res.json();
}

export async function uploadFile(chunksArray, uploadId)
{
    let data;
    for (let i = 0; i < chunksArray.length; i++) {
        const body = new FormData();
        body.append('upload_id',uploadId);
        body.append('file',chunksArray[i]);
        body.append('chunk_index',i);
        body.append('total_chunks', chunksArray.length);
        const res = await fetch('/api/imports/chunk', {method: 'POST', headers, body});
        data = await res.json()
        if (!res.ok)
        {
            throw new Error(data.message ?? 'Upload failed');
        }

    }
    return data;
}
