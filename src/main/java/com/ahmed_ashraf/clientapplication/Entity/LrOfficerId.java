package com.ahmed_ashraf.clientapplication.Entity;

import jakarta.persistence.Column;
import jakarta.persistence.Embeddable;
import lombok.*;

import java.io.Serializable;

@Embeddable
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class LrOfficerId implements Serializable {
    @Column(name = "BRANCH_CODE")
    private String branchCode;

    @Column(name = "CODE")
    private String code;
}
